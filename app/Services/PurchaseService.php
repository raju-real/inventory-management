<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\SupplierPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService extends BaseService
{
    public function __construct(private StockService $stock)
    {
    }

    public function paginate(array $filters)
    {
        return Purchase::rememberCache($this->listKey($filters), function () use ($filters) {
            $query = Purchase::with('supplier', 'creator')->latest('id');

            if (! empty($filters['search'])) {
                $search = '%' . $filters['search'] . '%';
                $query->where(fn ($q) => $q->where('reference_no', 'like', $search)
                    ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', $search)));
            }
            if (! empty($filters['party_id'])) {
                $query->where('supplier_id', $filters['party_id']);
            }
            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (! empty($filters['from'])) {
                $query->whereDate('purchase_date', '>=', $filters['from']);
            }
            if (! empty($filters['to'])) {
                $query->whereDate('purchase_date', '<=', $filters['to']);
            }

            return $query->paginate(static::PER_PAGE)->withQueryString();
        });
    }

    public function create(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $this->stock->lockProducts(array_column($data['items'], 'product_id'));
            // Totals are always worked out here; values sent by the browser are never trusted.
            $subtotal = 0;
            $quantity = 0;
            foreach ($data['items'] as $item) {
                $subtotal += round((float) $item['quantity'] * (float) $item['unit_price'], 2);
                $quantity += (float) $item['quantity'];
            }

            $discount = (float) ($data['discount'] ?? 0);
            $otherCharges = (float) ($data['other_charges'] ?? 0);
            $paid = (float) ($data['paid_amount'] ?? 0);
            $grandTotal = round($subtotal - $discount + $otherCharges, 2);

            if ($discount > $subtotal) {
                throw ValidationException::withMessages(['discount' => 'Discount cannot be more than the subtotal.']);
            }
            if ($paid > $grandTotal) {
                throw ValidationException::withMessages(['paid_amount' => 'Paid amount cannot be more than the grand total.']);
            }

            $purchase = Purchase::create([
                'purchase_date' => $data['purchase_date'],
                'supplier_id' => $data['supplier_id'],
                'total_quantity' => $quantity,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'other_charges' => $otherCharges,
                'grand_total' => $grandTotal,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $purchase->details()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => round((float) $item['quantity'] * (float) $item['unit_price'], 2),
                ]);

                $this->stock->in((int) $item['product_id'], (float) $item['quantity'], 'purchase', $purchase, $data['purchase_date'], (float) $item['unit_price']);
            }

            if ($paid > 0) {
                SupplierPayment::create([
                    'supplier_id' => $purchase->supplier_id,
                    'purchase_id' => $purchase->id,
                    'payment_date' => $data['purchase_date'],
                    'amount' => $paid,
                    'method' => $data['payment_method'] ?? 'cash',
                ]);
            }

            $purchase->recalculateBalance();

            return $purchase;
        }, 3);
    }

    public function cancel(Purchase $purchase, string $reason): void
    {
        DB::transaction(function () use ($purchase, $reason) {
            $purchase = Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            if ($purchase->isCancelled()) {
                throw ValidationException::withMessages(['purchase' => 'This purchase is already cancelled.']);
            }
            if ($purchase->returns()->posted()->exists()) {
                throw ValidationException::withMessages(['purchase' => 'Cancel the purchase returns of this invoice first.']);
            }

            $this->stock->lockProducts($purchase->details->pluck('product_id'));

            foreach ($purchase->details as $detail) {
                $this->stock->out($detail->product_id, (float) $detail->quantity, 'purchase_cancel', $purchase, now()->toDateString(), (float) $detail->unit_price, 'Cancelled: ' . $reason);
            }

            foreach ($purchase->payments()->posted()->get() as $payment) {
                $payment->markCancelled('Purchase cancelled');
            }

            $purchase->markCancelled($reason);
        }, 3);
    }
}
