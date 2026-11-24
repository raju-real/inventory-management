<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

/**
 * Works out a sale's figures. The sale screen shows the same calculation as a preview,
 * but only this one is saved.
 *
 *   subtotal = sum of quantity x unit price
 *   discount = percentage of the subtotal, or a flat amount
 *   VAT      = charged on what the customer pays after the discount:
 *              product wise: each line's VAT (percentage of the line after its share of the
 *              discount, or a flat amount per unit); on total order: one percentage or flat amount
 *   grand    = subtotal - discount + VAT
 */
class SaleCalculator
{
    /** The VAT a new sale starts with, from System Settings > VAT. The sale can still change it. */
    public static function vatDefaults(): array
    {
        return [
            'mode' => setting('vat_default_mode', 'product') === 'order' ? 'order' : 'product',
            'type' => setting('vat_default_type', 'percentage') === 'flat' ? 'flat' : 'percentage',
            'value' => (float) setting('vat_default_value', 0),
        ];
    }

    public function calculate(array $data): array
    {
        // A sale sent without any VAT choice gets the default from settings.
        if (! isset($data['vat_mode'])) {
            $defaults = static::vatDefaults();
            $data = ['vat_mode' => $defaults['mode'], 'vat_type' => $defaults['type'], 'vat_value' => $defaults['value']] + $data;
        }

        $lines = [];
        $subtotal = 0;
        $quantity = 0;
        foreach (array_values($data['items']) as $item) {
            $total = round((float) $item['quantity'] * (float) $item['unit_price'], 2);
            $lines[] = $item + ['total' => $total];
            $subtotal += $total;
            $quantity += (float) $item['quantity'];
        }
        $subtotal = round($subtotal, 2);

        $discountType = ($data['discount_type'] ?? 'percentage') === 'flat' ? 'flat' : 'percentage';
        $discountValue = round((float) ($data['discount_value'] ?? 0), 2);
        if ($discountType === 'percentage' && $discountValue > 100) {
            throw ValidationException::withMessages(['discount_value' => 'A percentage discount cannot be more than 100%.']);
        }
        $discount = $discountType === 'percentage' ? round($subtotal * $discountValue / 100, 2) : $discountValue;
        if ($discount > $subtotal) {
            throw ValidationException::withMessages(['discount_value' => 'Discount cannot be more than the subtotal.']);
        }

        // Each line's share of the discount, so VAT is charged on what is really paid for it.
        $nets = [];
        foreach ($this->spread($discount, array_column($lines, 'total')) as $index => $share) {
            $nets[$index] = round($lines[$index]['total'] - $share, 2);
        }

        $vatMode = ($data['vat_mode'] ?? 'product') === 'order' ? 'order' : 'product';
        $orderVatType = null;
        $orderVatValue = 0;

        if ($vatMode === 'product') {
            foreach ($lines as $index => $line) {
                $type = ($line['vat_type'] ?? 'percentage') === 'flat' ? 'flat' : 'percentage';
                $value = round((float) ($line['vat_value'] ?? 0), 2);
                if ($type === 'percentage' && $value > 100) {
                    throw ValidationException::withMessages(["items.{$index}.vat_value" => 'VAT cannot be more than 100%.']);
                }
                $lines[$index]['vat_type'] = $type;
                $lines[$index]['vat_value'] = $value;
                $lines[$index]['vat_amount'] = $type === 'percentage' ? round($nets[$index] * $value / 100, 2) : round($value * (float) $line['quantity'], 2);
            }
            $vat = round(array_sum(array_column($lines, 'vat_amount')), 2);
        } else {
            $orderVatType = ($data['vat_type'] ?? 'percentage') === 'flat' ? 'flat' : 'percentage';
            $orderVatValue = round((float) ($data['vat_value'] ?? 0), 2);
            if ($orderVatType === 'percentage' && $orderVatValue > 100) {
                throw ValidationException::withMessages(['vat_value' => 'VAT cannot be more than 100%.']);
            }
            $vat = $orderVatType === 'percentage' ? round(($subtotal - $discount) * $orderVatValue / 100, 2) : $orderVatValue;

            // Shared over the lines too, so a returned item takes its part of the VAT back.
            foreach ($this->spread($vat, $nets) as $index => $part) {
                $lines[$index]['vat_type'] = $orderVatType;
                $lines[$index]['vat_value'] = $orderVatType === 'percentage' ? $orderVatValue : 0;
                $lines[$index]['vat_amount'] = $part;
            }
        }

        return [
            'lines' => $lines,
            'quantity' => $quantity,
            'subtotal' => $subtotal,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'discount' => $discount,
            'vat_mode' => $vatMode,
            'vat_type' => $orderVatType,
            'vat_value' => $orderVatValue,
            'vat_amount' => $vat,
            'grand_total' => round($subtotal - $discount + $vat, 2),
        ];
    }

    /** Splits an amount in proportion to the weights, to the paisa, so the parts add up exactly. */
    private function spread(float $amount, array $weights): array
    {
        $parts = array_fill(0, count($weights), 0.0);
        $total = array_sum($weights);
        if (! $weights) {
            return $parts;
        }
        if ($total <= 0) {
            $parts[0] = $amount;

            return $parts;
        }

        $given = 0;
        $last = count($weights) - 1;
        foreach (array_values($weights) as $index => $weight) {
            $parts[$index] = $index === $last ? round($amount - $given, 2) : round($amount * $weight / $total, 2);
            $given += $parts[$index];
        }

        return $parts;
    }
}
