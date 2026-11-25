<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Money still owed: by customers on sales, or to suppliers on purchases.
 *
 * Invoice wise lists every posted invoice with a due amount. Party wise totals those invoices per
 * customer or supplier, and adds what is not tied to an invoice (opening balance, payments made
 * without choosing an invoice), so the total due always equals the party's real balance.
 */
class DueService extends BaseService
{
    public const TYPES = [
        'customers' => [
            'label' => 'Customer', 'plural' => 'Customers', 'party' => 'customers', 'invoice' => 'sales', 'key' => 'customer_id',
            'date' => 'sale_date', 'payments' => 'customer_payments', 'returns' => 'sales_returns', 'points' => true,
            'paid' => 'Received', 'route' => 'sales.show',
        ],
        'suppliers' => [
            'label' => 'Supplier', 'plural' => 'Suppliers', 'party' => 'suppliers', 'invoice' => 'purchases', 'key' => 'supplier_id',
            'date' => 'purchase_date', 'payments' => 'supplier_payments', 'returns' => 'purchase_returns', 'points' => false,
            'paid' => 'Paid', 'route' => 'purchases.show',
        ],
    ];

    public static function type(?string $type): string
    {
        return array_key_exists((string) $type, self::TYPES) ? $type : 'customers';
    }

    /** One row per party that owes (or is owed) money, largest due first. */
    public function partyWise(string $type, array $filters, bool $paginate = true)
    {
        $query = $this->partyQuery($type, $filters)->orderByDesc('balance')->orderBy('name');

        return $paginate ? $query->paginate(static::PER_PAGE)->withQueryString() : $query->get();
    }

    public function partyTotals(string $type, array $filters): object
    {
        return DB::query()->fromSub($this->partyQuery($type, $filters), 't')->selectRaw(
            'COUNT(*) AS parties, COALESCE(SUM(due_invoices), 0) AS due_invoices, COALESCE(SUM(invoice_total), 0) AS invoice_total,
             COALESCE(SUM(invoice_paid), 0) AS invoice_paid, COALESCE(SUM(invoice_returned), 0) AS invoice_returned,
             COALESCE(SUM(invoice_due), 0) AS invoice_due, COALESCE(SUM(other), 0) AS other, COALESCE(SUM(balance), 0) AS balance'
        )->first();
    }

    /** Every posted invoice that still has a due, oldest first within each party. */
    public function invoiceWise(string $type, array $filters, bool $paginate = true)
    {
        $query = $this->invoiceQuery($type, $filters)
            ->orderBy('p.name')->orderBy('p.id')->orderBy('invoice_date')->orderBy('i.id');

        return $paginate ? $query->paginate(static::PER_PAGE)->withQueryString() : $query->get();
    }

    public function invoiceTotals(string $type, array $filters): object
    {
        return DB::query()->fromSub($this->invoiceQuery($type, $filters), 't')->selectRaw(
            'COUNT(*) AS invoices, COUNT(DISTINCT party_id) AS parties, COALESCE(SUM(grand_total), 0) AS grand_total,
             COALESCE(SUM(paid), 0) AS paid, COALESCE(SUM(returned), 0) AS returned, COALESCE(SUM(due), 0) AS due'
        )->first();
    }

    /**
     * For the statement PDF: each party with its due invoices and a total due row.
     * Without a date filter the total includes the opening balance and unlinked payments,
     * so it matches the party's balance. With a date filter it is the due of the listed invoices only.
     */
    public function statement(string $type, array $filters): Collection
    {
        $dated = ! empty($filters['from']) || ! empty($filters['to']);
        $invoices = $this->invoiceWise($type, $filters, false)->groupBy('party_id');
        $parties = $dated ? collect() : $this->partyWise($type, $filters, false)->keyBy('id');

        $ids = $invoices->keys()->merge($parties->keys())->unique();
        $names = DB::table(self::TYPES[$type]['party'])->whereIn('id', $ids)->get(['id', 'name', 'mobile', 'address'])->keyBy('id');

        return $ids->map(function ($id) use ($invoices, $parties, $names, $dated) {
            $rows = $invoices->get($id, collect());
            $invoiceDue = round((float) $rows->sum('due'), 2);
            $party = $parties->get($id);
            $other = $dated || ! $party ? 0.0 : round((float) $party->balance - $invoiceDue, 2);

            return (object) [
                'id' => $id,
                'name' => $names[$id]->name ?? '',
                'mobile' => $names[$id]->mobile ?? null,
                'address' => $names[$id]->address ?? null,
                'invoices' => $rows,
                'invoice_total' => round((float) $rows->sum('grand_total'), 2),
                'paid' => round((float) $rows->sum('paid'), 2),
                'returned' => round((float) $rows->sum('returned'), 2),
                'invoice_due' => $invoiceDue,
                'other' => abs($other) >= 0.005 ? $other : 0.0,
                'total_due' => round($invoiceDue + (abs($other) >= 0.005 ? $other : 0), 2),
            ];
        })->sortBy(fn ($party) => mb_strtolower($party->name))->values();
    }

    private function partyQuery(string $type, array $filters)
    {
        $t = self::TYPES[$type];
        $sum = fn (string $table, string $expression, ?callable $where = null) => DB::table($table)
            ->selectRaw("COALESCE(SUM({$expression}), 0)")
            ->whereColumn("{$table}.{$t['key']}", 'p.id')->where("{$table}.status", 'posted')
            ->when($where, $where);
        $due = fn ($query) => $query->where('due_amount', '>', 0);
        $paidExpression = $t['points'] ? 'paid_amount + points_amount' : 'paid_amount';

        $inner = DB::table($t['party'] . ' as p')
            ->select('p.id', 'p.unique_id', 'p.name', 'p.mobile', 'p.opening_balance')
            ->selectSub(DB::table($t['invoice'])->selectRaw('COUNT(*)')->whereColumn($t['key'], 'p.id')->where('status', 'posted')->where('due_amount', '>', 0), 'due_invoices')
            ->selectSub($sum($t['invoice'], 'grand_total', $due), 'invoice_total')
            ->selectSub($sum($t['invoice'], $paidExpression, $due), 'invoice_paid')
            ->selectSub($sum($t['invoice'], 'returned_amount', $due), 'invoice_returned')
            ->selectSub($sum($t['invoice'], 'due_amount'), 'invoice_due')
            ->selectSub($sum($t['invoice'], 'grand_total'), 'invoiced')
            ->selectSub($sum($t['payments'], 'amount'), 'payments')
            ->selectSub($sum($t['returns'], 'total_amount'), 'returns')
            ->selectSub($sum($t['returns'], 'refund_amount'), 'refunds')
            ->selectSub($t['points'] ? $sum($t['invoice'], 'points_amount') : DB::query()->selectRaw('0'), 'points');

        if (! empty($filters['party_id'])) {
            $inner->where('p.id', $filters['party_id']);
        }

        // Same formula as the party's balance on its page and ledger.
        $balance = 'ROUND(d.opening_balance + d.invoiced - d.payments - d.returns + d.refunds - d.points, 2)';

        return DB::query()->fromSub($inner, 'd')
            ->selectRaw("d.*, {$balance} AS balance, ROUND({$balance} - d.invoice_due, 2) AS other")
            ->where(fn ($query) => $query->whereRaw("{$balance} > 0.004")->orWhere('d.due_invoices', '>', 0));
    }

    private function invoiceQuery(string $type, array $filters)
    {
        $t = self::TYPES[$type];
        $paid = $t['points'] ? 'i.paid_amount + i.points_amount' : 'i.paid_amount';

        return DB::table($t['invoice'] . ' as i')
            ->join($t['party'] . ' as p', 'p.id', '=', "i.{$t['key']}")
            ->where('i.status', 'posted')->where('i.due_amount', '>', 0)
            ->when(! empty($filters['party_id']), fn ($query) => $query->where('p.id', $filters['party_id']))
            ->when(! empty($filters['from']), fn ($query) => $query->whereDate("i.{$t['date']}", '>=', $filters['from']))
            ->when(! empty($filters['to']), fn ($query) => $query->whereDate("i.{$t['date']}", '<=', $filters['to']))
            ->selectRaw("i.id, i.unique_id, i.reference_no, i.{$t['date']} AS invoice_date, p.id AS party_id, p.unique_id AS party_unique_id,
                p.name AS party_name, p.mobile AS party_mobile, i.grand_total, {$paid} AS paid, i.returned_amount AS returned, i.due_amount AS due");
    }
}
