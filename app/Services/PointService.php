<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\PointTransaction;
use App\Models\Sale;
use App\Models\SalesReturn;

/**
 * Loyalty points. A customer's balance only changes here, and every change writes a
 * point transaction, the same way stock works. Call inside DB::transaction().
 */
class PointService
{
    public function settings(): array
    {
        return [
            'earn_enabled' => setting('points_earn_enabled', '1') === '1',
            'earn_amount' => (float) setting('points_earn_amount', 100),
            'redeem_enabled' => setting('points_redeem_enabled', '1') === '1',
            'point_value' => (float) setting('points_value', 0.70),
        ];
    }

    /** Points earned for money paid: 1 point for every full earn_amount, rounded down. */
    public function pointsFor(float $amount): int
    {
        $settings = $this->settings();

        if (! $settings['earn_enabled'] || $settings['earn_amount'] <= 0 || $amount <= 0) {
            return 0;
        }

        return (int) floor(round($amount, 2) / $settings['earn_amount'] + 1e-9);
    }

    /** The customer's balance, with the row locked until the transaction ends. */
    public function lockedBalance(int $customerId): int
    {
        return (int) Customer::whereKey($customerId)->lockForUpdate()->value('points');
    }

    public function record(int $customerId, int $points, string $type, ?Sale $sale = null, ?SalesReturn $return = null, float $amount = 0, ?string $remarks = null): void
    {
        if ($points === 0) {
            return;
        }

        $customer = Customer::whereKey($customerId)->lockForUpdate()->firstOrFail();
        $customer->points = (int) $customer->points + $points;
        $customer->save();

        PointTransaction::create([
            'customer_id' => $customerId,
            'sale_id' => $sale?->id,
            'sales_return_id' => $return?->id,
            'type' => $type,
            'points' => $points,
            'amount' => round($amount, 2),
            'remarks' => $remarks,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Returns take back the earned points in proportion to the value returned, so a full
     * return takes back all of them. Works after both a return and its cancellation.
     */
    public function syncReturns(Sale $sale, ?SalesReturn $return = null): void
    {
        $sale->refresh();
        if ((int) $sale->points_earned === 0) {
            return;
        }

        $returned = (float) $sale->returns()->posted()->sum('total_amount');
        $share = (float) $sale->grand_total > 0 ? min(1, $returned / (float) $sale->grand_total) : 0;
        $target = (int) floor($sale->points_earned * $share + 1e-9);
        $change = $target - (int) $sale->points_reversed;

        if ($change !== 0) {
            $this->record($sale->customer_id, -$change, 'returned', $sale, $return, 0, $change > 0 ? 'Items returned' : 'Return cancelled');
            $sale->points_reversed = $target;
            $sale->save();
        }
    }

    /** A cancelled sale takes back what it earned and gives back what it used. */
    public function reverseSale(Sale $sale): void
    {
        $earned = (int) $sale->points_earned - (int) $sale->points_reversed;
        $this->record($sale->customer_id, -$earned, 'cancelled', $sale, null, 0, 'Points earned on the sale');
        $this->record($sale->customer_id, (int) $sale->points_redeemed, 'cancelled', $sale, null, (float) $sale->points_amount, 'Points used on the sale');
    }
}
