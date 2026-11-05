<?php

namespace App\Services;

use App\Models\CustomerPayment;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService extends BaseService
{
    public function __construct(private StockService $stock)
    {
    }

    public function paginate(array $filters)
    {
        return Sale::rememberCache($this->listKey($filters), function () use ($filters) {
            $query = Sale::with('customer', 'creator')->latest('id');

            if (! empty($filters['search'])) {
                $search = '%' . $filters['search'] . '%';
                $query->where(fn ($q) => $q->where('reference_no', 'like', $search)
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $search)->orWhere('mobile', 'like', $search)));
            }
            if (! empty($filters['party_id'])) {
                $query->where('customer_id', $filters['party_id']);
            }
            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (! empty($filters['from'])) {
                $query->whereDate('sale_date', '>=', $filters['from']);
            }
            if (! empty($filters['to'])) {
                $query->whereDate('sale_date', '<=', $filters['to']);
            }

            return $query->paginate(static::PER_PAGE)->withQueryString();
        });
    }

    public function create(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $this->stock->lockProducts(array_column($data['items'], 'product_id'));
            $subtotal = 0;
            $quantity = 0;
            foreach ($data['items'] as $item) {
                $subtotal += round((float) $item['quantity'] * (float) $item['unit_price'], 2);
                $quantity += (float) $item['quantity'];
            }

            $discount = (float) ($data['discount'] ?? 0);
            $paid = (float) ($data['paid_amount'] ?? 0);
            $grandTotal = round($subtotal - $discount, 2);

            if ($discount > $subtotal) {
                throw ValidationException::withMessages(['discount' => 'Discount cannot be more than the subtotal.']);
            }
            if ($paid > $grandTotal) {
                throw ValidationException::withMessages(['paid_amount' => 'Paid amount cannot be more than the grand total.']);
            }

            $sale = Sale::create([
                'sale_date' => $data['sale_date'],
                'customer_id' => $data['customer_id'],
                'total_quantity' => $quantity,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'grand_total' => $grandTotal,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $sale->details()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => round((float) $item['quantity'] * (float) $item['unit_price'], 2),
                ]);

                // Throws (and rolls everything back) when stock is not enough.
                $this->stock->out((int) $item['product_id'], (float) $item['quantity'], 'sale', $sale, $data['sale_date']);
            }

            if ($paid > 0) {
                CustomerPayment::create([
                    'customer_id' => $sale->customer_id,
                    'sale_id' => $sale->id,
                    'payment_date' => $data['sale_date'],
                    'amount' => $paid,
                    'method' => $data['payment_method'] ?? 'cash',
                ]);
            }

            $sale->recalculateBalance();

            return $sale;
        }, 3);
    }

    public function cancel(Sale $sale, string $reason): void
    {
        DB::transaction(function () use ($sale, $reason) {
            $sale = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();

            if ($sale->isCancelled()) {
                throw ValidationException::withMessages(['sale' => 'This sale is already cancelled.']);
            }
            if ($sale->returns()->posted()->exists()) {
                throw ValidationException::withMessages(['sale' => 'Cancel the sales returns of this invoice first.']);
            }

            $this->stock->lockProducts($sale->details->pluck('product_id'));

            foreach ($sale->details as $detail) {
                $this->stock->in($detail->product_id, (float) $detail->quantity, 'sale_cancel', $sale, now()->toDateString(), null, 'Cancelled: ' . $reason);
            }

            foreach ($sale->payments()->posted()->get() as $payment) {
                $payment->markCancelled('Sale cancelled');
            }

            $sale->markCancelled($reason);
        }, 3);
    }
}
