<?php

namespace App\Models\Concerns;

/** Keeps paid / returned / due on a purchase or sale in step with its payments and returns. */
trait HasInvoiceBalance
{
    public function recalculateBalance(): void
    {
        $paid = (float) $this->payments()->posted()->sum('amount');
        $returned = $this->returns()->posted()->get()
            ->sum(fn ($return) => (float) $return->total_amount - (float) $return->refund_amount);

        $this->paid_amount = round($paid, 2);
        $this->returned_amount = round($returned, 2);
        $this->due_amount = max(0, round((float) $this->grand_total - $returned - $paid - (float) ($this->points_amount ?? 0), 2));
        $this->save();
    }

    /** Share of the line price left after the invoice discount. */
    public function netRatio(): float
    {
        $subtotal = (float) $this->subtotal;

        return $subtotal > 0 ? ($subtotal - (float) $this->discount) / $subtotal : 1;
    }

    /** Value of one unit after the invoice discount. */
    public function netUnitPrice(float $unitPrice): float
    {
        return round($unitPrice * $this->netRatio(), 2);
    }

    /**
     * Value of a returned line. Worked out from the whole line, and capped so that rounding
     * can never make the returns of an invoice worth more than the goods on it.
     */
    public function returnLineValue(float $quantity, float $unitPrice, float $inThisReturn = 0, float $vatPerUnit = 0): float
    {
        $goodsValue = (float) $this->subtotal - (float) $this->discount + (float) ($this->vat_amount ?? 0);
        $alreadyReturned = (float) $this->returns()->posted()->sum('total_amount');

        return round(min($quantity * ($unitPrice * $this->netRatio() + $vatPerUnit), max(0, $goodsValue - $alreadyReturned - $inThisReturn)), 2);
    }

    public function getPaymentStatusAttribute(): string
    {
        if ((float) $this->due_amount <= 0) {
            return 'Paid';
        }

        return (float) $this->paid_amount > 0 ? 'Partial' : 'Due';
    }
}
