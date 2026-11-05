<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class LedgerService extends BaseService
{
    /** Debit raises what the customer owes, credit lowers it. */
    public function customer(Customer $customer, array $filters): array
    {
        $rows = collect();

        foreach ($customer->sales()->posted()->get() as $sale) {
            $rows->push($this->row($sale->sale_date, 'Sale', $sale->reference_no, $sale->grand_total, 0));
        }
        foreach ($customer->payments()->posted()->get() as $payment) {
            $rows->push($this->row($payment->payment_date, 'Payment Received', $payment->reference_no, 0, $payment->amount));
        }
        foreach ($customer->salesReturns()->posted()->get() as $return) {
            $rows->push($this->row($return->return_date, 'Sales Return', $return->reference_no, 0, $return->total_amount));
            if ((float) $return->refund_amount > 0) {
                $rows->push($this->row($return->return_date, 'Refund Paid', $return->reference_no, $return->refund_amount, 0));
            }
        }

        return $this->build($rows, (float) $customer->opening_balance, $filters);
    }

    /** Credit raises what we owe the supplier, debit lowers it. */
    public function supplier(Supplier $supplier, array $filters): array
    {
        $rows = collect();

        foreach ($supplier->purchases()->posted()->get() as $purchase) {
            $rows->push($this->row($purchase->purchase_date, 'Purchase', $purchase->reference_no, 0, $purchase->grand_total));
        }
        foreach ($supplier->payments()->posted()->get() as $payment) {
            $rows->push($this->row($payment->payment_date, 'Payment Made', $payment->reference_no, $payment->amount, 0));
        }
        foreach ($supplier->purchaseReturns()->posted()->get() as $return) {
            $rows->push($this->row($return->return_date, 'Purchase Return', $return->reference_no, $return->total_amount, 0));
            if ((float) $return->refund_amount > 0) {
                $rows->push($this->row($return->return_date, 'Refund Received', $return->reference_no, 0, $return->refund_amount));
            }
        }

        return $this->build($rows, (float) $supplier->opening_balance, $filters, true);
    }

    private function row($date, string $type, string $reference, $debit, $credit): array
    {
        return ['date' => $date->toDateString(), 'type' => $type, 'reference' => $reference, 'debit' => (float) $debit, 'credit' => (float) $credit];
    }

    private function build(Collection $rows, float $opening, array $filters, bool $creditRaises = false): array
    {
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;
        $change = fn ($row) => $creditRaises ? $row['credit'] - $row['debit'] : $row['debit'] - $row['credit'];

        $rows = $rows->sortBy('date')->values();
        $openingBalance = $opening + $rows->filter(fn ($row) => $from && $row['date'] < $from)->sum($change);

        $balance = $openingBalance;
        $visible = $rows
            ->filter(fn ($row) => (! $from || $row['date'] >= $from) && (! $to || $row['date'] <= $to))
            ->map(function ($row) use (&$balance, $change) {
                $balance += $change($row);
                $row['balance'] = round($balance, 2);

                return $row;
            })
            ->values();

        $page = max(1, (int) request('page', 1));
        $paginator = new LengthAwarePaginator(
            $visible->forPage($page, static::PER_PAGE)->values(),
            $visible->count(),
            static::PER_PAGE,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return [
            'rows' => $paginator,
            'opening' => round($openingBalance, 2),
            'debit' => round($visible->sum('debit'), 2),
            'credit' => round($visible->sum('credit'), 2),
            'closing' => round($balance, 2),
        ];
    }
}
