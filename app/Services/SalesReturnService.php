<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\SalesReturn;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesReturnService extends BaseService
{
    public function __construct(private StockService $stock)
    {
    }

    public function paginate(array $filters)
    {
        return SalesReturn::rememberCache($this->listKey($filters), function () use ($filters) {
            $query = SalesReturn::with('sale', 'customer', 'creator')->latest('id');

            if (! empty($filters['search'])) {
                $search = '%' . $filters['search'] . '%';
                $query->where(fn ($q) => $q->where('reference_no', 'like', $search)
                    ->orWhereHas('sale', fn ($s) => $s->where('reference_no', 'like', $search))
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $search)));
            }
            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (! empty($filters['from'])) {
                $query->whereDate('return_date', '>=', $filters['from']);
            }
            if (! empty($filters['to'])) {
                $query->whereDate('return_date', '<=', $filters['to']);
            }

            return $query->paginate(static::PER_PAGE)->withQueryString();
        });
    }

    public function create(array $data): SalesReturn
    {
        return DB::transaction(function () use ($data) {
            $sale = Sale::whereKey($data['sale_id'])->lockForUpdate()->firstOrFail();
            $this->stock->lockProducts($sale->details()->pluck('product_id'));

            if ($sale->isCancelled()) {
                throw ValidationException::withMessages(['sale_id' => 'A cancelled invoice cannot be returned.']);
            }
            if ($data['return_date'] < $sale->sale_date->toDateString()) {
                throw ValidationException::withMessages(['return_date' => 'Return date cannot be before the sales date.']);
            }

            $items = collect($data['items'])->filter(fn ($item) => (float) ($item['quantity'] ?? 0) > 0);
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Enter a return quantity for at least one product.']);
            }

            $return = SalesReturn::create([
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'return_date' => $data['return_date'],
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $totalQuantity = 0;
            $totalAmount = 0;

            foreach ($items as $item) {
                $detail = SaleDetail::where('sale_id', $sale->id)->whereKey($item['sale_detail_id'])->lockForUpdate()->first();
                if (! $detail) {
                    throw ValidationException::withMessages(['items' => 'A returned product does not belong to this invoice.']);
                }

                $quantity = round((float) $item['quantity'], 3);
                if ($quantity > $detail->returnable_quantity) {
                    throw ValidationException::withMessages([
                        'items' => "{$detail->product->name}: only " . qty($detail->returnable_quantity) . ' can still be returned.',
                    ]);
                }

                $unitPrice = $sale->netUnitPrice((float) $detail->unit_price);
                $lineTotal = $sale->returnLineValue($quantity, (float) $detail->unit_price, $totalAmount);
                $condition = $item['condition'] ?? 'good';

                $return->details()->create([
                    'sale_detail_id' => $detail->id,
                    'product_id' => $detail->product_id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $lineTotal,
                    'condition' => $condition,
                ]);

                $detail->returned_quantity = round((float) $detail->returned_quantity + $quantity, 3);
                $detail->save();

                if ($condition === 'good') {
                    $this->stock->in($detail->product_id, $quantity, 'sales_return', $return, $data['return_date']);
                } else {
                    $this->stock->memo($detail->product_id, 'sales_return_damaged', $return, $data['return_date'], qty($quantity) . ' returned damaged, not added to stock');
                }

                $totalQuantity += $quantity;
                $totalAmount += $lineTotal;
            }

            $refund = (float) ($data['refund_amount'] ?? 0);
            if ($refund > $totalAmount) {
                throw ValidationException::withMessages(['refund_amount' => 'Refund cannot be more than the returned value (' . number_format($totalAmount, 2) . ').']);
            }

            $return->update(['total_quantity' => $totalQuantity, 'total_amount' => round($totalAmount, 2), 'refund_amount' => $refund]);
            $sale->recalculateBalance();

            return $return;
        }, 3);
    }

    public function cancel(SalesReturn $return, string $reason): void
    {
        DB::transaction(function () use ($return, $reason) {
            $return = SalesReturn::whereKey($return->id)->lockForUpdate()->firstOrFail();

            if ($return->isCancelled()) {
                throw ValidationException::withMessages(['return' => 'This return is already cancelled.']);
            }

            $this->stock->lockProducts($return->details->pluck('product_id'));

            foreach ($return->details as $detail) {
                $saleDetail = SaleDetail::whereKey($detail->sale_detail_id)->lockForUpdate()->firstOrFail();
                $saleDetail->returned_quantity = round((float) $saleDetail->returned_quantity - (float) $detail->quantity, 3);
                $saleDetail->save();

                if ($detail->condition === 'good') {
                    $this->stock->out($detail->product_id, (float) $detail->quantity, 'sales_return_cancel', $return, now()->toDateString(), null, 'Cancelled: ' . $reason);
                } else {
                    $this->stock->memo($detail->product_id, 'sales_return_cancel', $return, now()->toDateString(), 'Damaged return cancelled: ' . $reason);
                }
            }

            $return->markCancelled($reason);
            $return->sale->recalculateBalance();
        }, 3);
    }
}
