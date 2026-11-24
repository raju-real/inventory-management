<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService extends BaseService
{
    public function __construct(private StockService $stock, private SaleCalculator $calculator, private PointService $points)
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

            // Every figure is worked out here; values sent by the browser are never trusted.
            $totals = $this->calculator->calculate($data);
            $paid = round((float) ($data['paid_amount'] ?? 0), 2);

            $walkIn = (bool) Customer::whereKey($data['customer_id'])->value('is_walk_in');

            // Points pay part of the sale, like a payment. The balance is locked until saving ends.
            $pointsUsed = (int) ($data['points_redeemed'] ?? 0);
            $pointsAmount = 0;
            if ($pointsUsed > 0) {
                $settings = $this->points->settings();
                if (! $settings['redeem_enabled']) {
                    throw ValidationException::withMessages(['points_redeemed' => 'Using points is turned off in the settings.']);
                }
                if ($walkIn) {
                    throw ValidationException::withMessages(['points_redeemed' => 'Walk-in Customer has no points. Choose the customer.']);
                }
                $balance = $this->points->lockedBalance((int) $data['customer_id']);
                if ($pointsUsed > $balance) {
                    throw ValidationException::withMessages(['points_redeemed' => "The customer has only {$balance} points."]);
                }
                $pointsAmount = round($pointsUsed * $settings['point_value'], 2);
                if ($pointsAmount > $totals['grand_total']) {
                    throw ValidationException::withMessages(['points_redeemed' => 'These points are worth more than the sale. Use at most ' . (int) floor($totals['grand_total'] / $settings['point_value']) . ' points.']);
                }
            }
            $payable = round($totals['grand_total'] - $pointsAmount, 2);

            if ($paid > $payable) {
                throw ValidationException::withMessages(['paid_amount' => 'Paid amount cannot be more than the amount to pay.']);
            }
            if ($paid < $payable && $walkIn) {
                throw ValidationException::withMessages(['customer_id' => 'A sale with a due amount needs a named customer. Walk-in sales must be paid in full.']);
            }

            // Points are earned on money paid for the sale, not on points spent.
            $pointsEarned = $walkIn ? 0 : $this->points->pointsFor($payable);

            $sale = Sale::create([
                'sale_date' => $data['sale_date'],
                'customer_id' => $data['customer_id'],
                'total_quantity' => $totals['quantity'],
                'subtotal' => $totals['subtotal'],
                'discount_type' => $totals['discount_type'],
                'discount_value' => $totals['discount_value'],
                'discount' => $totals['discount'],
                'vat_mode' => $totals['vat_mode'],
                'vat_type' => $totals['vat_type'],
                'vat_value' => $totals['vat_value'],
                'vat_amount' => $totals['vat_amount'],
                'points_redeemed' => $pointsUsed,
                'points_amount' => $pointsAmount,
                'points_earned' => $pointsEarned,
                'grand_total' => $totals['grand_total'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($totals['lines'] as $line) {
                $sale->details()->create([
                    'product_id' => $line['product_id'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'total' => $line['total'],
                    'vat_type' => $line['vat_type'],
                    'vat_value' => $line['vat_value'],
                    'vat_amount' => $line['vat_amount'],
                ]);

                // Throws (and rolls everything back) when stock is not enough.
                $this->stock->out((int) $line['product_id'], (float) $line['quantity'], 'sale', $sale, $data['sale_date']);
            }

            $this->points->record($sale->customer_id, -$pointsUsed, 'redeemed', $sale, null, $pointsAmount);
            $this->points->record($sale->customer_id, $pointsEarned, 'earned', $sale);

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

            $this->points->reverseSale($sale);
            $sale->markCancelled($reason);
        }, 3);
    }
}
