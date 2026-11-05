<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\PurchaseReturn;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseReturnService extends BaseService
{
    public function __construct(private StockService $stock)
    {
    }

    public function paginate(array $filters)
    {
        return PurchaseReturn::rememberCache($this->listKey($filters), function () use ($filters) {
            $query = PurchaseReturn::with('purchase', 'supplier', 'creator')->latest('id');

            if (! empty($filters['search'])) {
                $search = '%' . $filters['search'] . '%';
                $query->where(fn ($q) => $q->where('reference_no', 'like', $search)
                    ->orWhereHas('purchase', fn ($p) => $p->where('reference_no', 'like', $search))
                    ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', $search)));
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

    public function create(array $data): PurchaseReturn
    {
        return DB::transaction(function () use ($data) {
            $purchase = Purchase::whereKey($data['purchase_id'])->lockForUpdate()->firstOrFail();
            $this->stock->lockProducts($purchase->details()->pluck('product_id'));

            if ($purchase->isCancelled()) {
                throw ValidationException::withMessages(['purchase_id' => 'A cancelled invoice cannot be returned.']);
            }
            if ($data['return_date'] < $purchase->purchase_date->toDateString()) {
                throw ValidationException::withMessages(['return_date' => 'Return date cannot be before the purchase date.']);
            }

            $items = collect($data['items'])->filter(fn ($item) => (float) ($item['quantity'] ?? 0) > 0);
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Enter a return quantity for at least one product.']);
            }

            $return = PurchaseReturn::create([
                'purchase_id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'return_date' => $data['return_date'],
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $totalQuantity = 0;
            $totalAmount = 0;

            foreach ($items as $item) {
                $detail = PurchaseDetail::where('purchase_id', $purchase->id)->whereKey($item['purchase_detail_id'])->lockForUpdate()->first();
                if (! $detail) {
                    throw ValidationException::withMessages(['items' => 'A returned product does not belong to this invoice.']);
                }

                $quantity = round((float) $item['quantity'], 3);
                if ($quantity > $detail->returnable_quantity) {
                    throw ValidationException::withMessages([
                        'items' => "{$detail->product->name}: only " . qty($detail->returnable_quantity) . ' can still be returned.',
                    ]);
                }

                $unitPrice = $purchase->netUnitPrice((float) $detail->unit_price);
                $lineTotal = $purchase->returnLineValue($quantity, (float) $detail->unit_price, $totalAmount);

                $return->details()->create([
                    'purchase_detail_id' => $detail->id,
                    'product_id' => $detail->product_id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $lineTotal,
                ]);

                $detail->returned_quantity = round((float) $detail->returned_quantity + $quantity, 3);
                $detail->save();

                $this->stock->out($detail->product_id, $quantity, 'purchase_return', $return, $data['return_date'], $unitPrice);

                $totalQuantity += $quantity;
                $totalAmount += $lineTotal;
            }

            $refund = (float) ($data['refund_amount'] ?? 0);
            if ($refund > $totalAmount) {
                throw ValidationException::withMessages(['refund_amount' => 'Refund cannot be more than the returned value (' . number_format($totalAmount, 2) . ').']);
            }

            $return->update(['total_quantity' => $totalQuantity, 'total_amount' => round($totalAmount, 2), 'refund_amount' => $refund]);
            $purchase->recalculateBalance();

            return $return;
        }, 3);
    }

    public function cancel(PurchaseReturn $return, string $reason): void
    {
        DB::transaction(function () use ($return, $reason) {
            $return = PurchaseReturn::whereKey($return->id)->lockForUpdate()->firstOrFail();

            if ($return->isCancelled()) {
                throw ValidationException::withMessages(['return' => 'This return is already cancelled.']);
            }

            $this->stock->lockProducts($return->details->pluck('product_id'));

            foreach ($return->details as $detail) {
                $purchaseDetail = PurchaseDetail::whereKey($detail->purchase_detail_id)->lockForUpdate()->firstOrFail();
                $purchaseDetail->returned_quantity = round((float) $purchaseDetail->returned_quantity - (float) $detail->quantity, 3);
                $purchaseDetail->save();

                $this->stock->in($detail->product_id, (float) $detail->quantity, 'purchase_return_cancel', $return, now()->toDateString(), (float) $detail->unit_price, 'Cancelled: ' . $reason);
            }

            $return->markCancelled($reason);
            $return->purchase->recalculateBalance();
        }, 3);
    }
}
