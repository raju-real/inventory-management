<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class ReportService extends BaseService
{
    public function __construct(private StockService $stock)
    {
    }

    public function stock(array $filters)
    {
        return $this->stock->overview($filters);
    }

    public function lowStock(array $filters)
    {
        return $this->stock->overview($filters + ['low_stock' => 1]);
    }

    /** Extra figures each report shows between quantity and total: column => [label, money or count]. */
    private const PURCHASE_COLUMNS = ['subtotal' => ['Subtotal', 'money'], 'discount' => ['Discount', 'money'], 'other_charges' => ['Other charges', 'money']];
    private const SALE_COLUMNS = ['subtotal' => ['Subtotal', 'money'], 'discount' => ['Discount', 'money'], 'vat_amount' => ['VAT', 'money'],
        'points_amount' => ['Points used', 'money'], 'points_earned' => ['Points earned', 'count']];

    public function purchases(array $filters): array
    {
        return $this->invoices(Purchase::class, 'purchase_date', 'supplier_id', 'suppliers', $filters, self::PURCHASE_COLUMNS);
    }

    public function sales(array $filters): array
    {
        return $this->invoices(Sale::class, 'sale_date', 'customer_id', 'customers', $filters, self::SALE_COLUMNS);
    }

    /** Invoice wise, date wise or party wise report for purchases and sales. */
    private function invoices(string $model, string $dateColumn, string $partyColumn, string $partyTable, array $filters, array $columns): array
    {
        return $model::rememberCache('report:' . $this->listKey($filters), function () use ($model, $dateColumn, $partyColumn, $partyTable, $filters, $columns) {
            $table = (new $model())->getTable();
            $sums = collect(array_keys($columns))->map(fn ($column) => "COALESCE(SUM({$table}.{$column}), 0) AS {$column}")->join(', ');
            $base = $model::query()
                ->join($partyTable, "{$table}.{$partyColumn}", '=', "{$partyTable}.id")
                ->where("{$table}.status", 'posted');

            if (! empty($filters['from'])) {
                $base->whereDate("{$table}.{$dateColumn}", '>=', $filters['from']);
            }
            if (! empty($filters['to'])) {
                $base->whereDate("{$table}.{$dateColumn}", '<=', $filters['to']);
            }
            if (! empty($filters['party_id'])) {
                $base->where("{$table}.{$partyColumn}", $filters['party_id']);
            }

            $totals = (clone $base)->selectRaw("COUNT(*) AS invoices, COALESCE(SUM({$table}.total_quantity), 0) AS quantity, {$sums},
                COALESCE(SUM({$table}.grand_total), 0) AS total, COALESCE(SUM({$table}.paid_amount), 0) AS paid,
                COALESCE(SUM({$table}.due_amount), 0) AS due")->toBase()->first();

            $group = $filters['group'] ?? 'invoice';
            $aggregates = "COUNT(*) AS invoices, SUM({$table}.total_quantity) AS quantity, {$sums}, SUM({$table}.grand_total) AS total,
                SUM({$table}.paid_amount) AS paid, SUM({$table}.due_amount) AS due";

            if ($group === 'date') {
                $rows = $base->selectRaw("{$table}.{$dateColumn} AS label, {$aggregates}")
                    ->groupBy("{$table}.{$dateColumn}")->orderByDesc("{$table}.{$dateColumn}");
            } elseif ($group === 'party') {
                $rows = $base->selectRaw("{$partyTable}.name AS label, {$aggregates}")
                    ->groupBy("{$partyTable}.id", "{$partyTable}.name")->orderBy("{$partyTable}.name");
            } else {
                $rows = $base->select(array_merge(
                    ["{$table}.reference_no", "{$table}.{$dateColumn} AS invoice_date", "{$partyTable}.name AS party_name", "{$table}.total_quantity AS quantity"],
                    array_map(fn ($column) => "{$table}.{$column}", array_keys($columns)),
                    ["{$table}.grand_total AS total", "{$table}.paid_amount AS paid", "{$table}.due_amount AS due"]
                ))->orderByDesc("{$table}.id");
            }

            return ['rows' => $rows->toBase()->paginate(static::PER_PAGE)->withQueryString(), 'totals' => $totals, 'columns' => $columns];
        });
    }

    /** $type: damage, sales_return or purchase_return. */
    public function damageReturns(string $type, array $filters): array
    {
        $query = match ($type) {
            'sales_return' => $this->salesReturnQuery(),
            'purchase_return' => $this->purchaseReturnQuery(),
            default => $this->damageQuery(),
        };

        $query = DB::query()->fromSub($query, 'rows');

        if (! empty($filters['from'])) {
            $query->whereDate('entry_date', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('entry_date', '<=', $filters['to']);
        }
        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        $totals = (clone $query)->selectRaw('COALESCE(SUM(quantity), 0) AS quantity, COALESCE(SUM(amount), 0) AS amount')->first();

        return [
            'rows' => $query->orderByDesc('entry_date')->orderBy('reference_no')->paginate(static::PER_PAGE)->withQueryString(),
            'totals' => $totals,
        ];
    }

    /** Recorded damages plus customer returns received in damaged condition. */
    private function damageQuery()
    {
        $recorded = DB::table('damage_details as d')
            ->join('damages as m', 'm.id', '=', 'd.damage_id')
            ->join('products as p', 'p.id', '=', 'd.product_id')
            ->where('m.status', 'posted')
            ->selectRaw("m.reference_no, m.damage_date AS entry_date, d.product_id, p.name AS product_name, d.quantity, d.quantity * d.unit_cost AS amount, d.reason AS detail, 'Damage entry' AS source");

        $returned = DB::table('sales_return_details as d')
            ->join('sales_returns as m', 'm.id', '=', 'd.sales_return_id')
            ->join('products as p', 'p.id', '=', 'd.product_id')
            ->where('m.status', 'posted')
            ->where('d.condition', 'damaged')
            ->selectRaw("m.reference_no, m.return_date AS entry_date, d.product_id, p.name AS product_name, d.quantity, d.total AS amount, m.reason AS detail, 'Customer return' AS source");

        return $recorded->unionAll($returned);
    }

    private function salesReturnQuery()
    {
        return DB::table('sales_return_details as d')
            ->join('sales_returns as m', 'm.id', '=', 'd.sales_return_id')
            ->join('products as p', 'p.id', '=', 'd.product_id')
            ->join('customers as c', 'c.id', '=', 'm.customer_id')
            ->where('m.status', 'posted')
            ->selectRaw('m.reference_no, m.return_date AS entry_date, d.product_id, p.name AS product_name, d.quantity, d.total AS amount, m.reason AS detail, c.name AS source, d.condition AS item_condition');
    }

    private function purchaseReturnQuery()
    {
        return DB::table('purchase_return_details as d')
            ->join('purchase_returns as m', 'm.id', '=', 'd.purchase_return_id')
            ->join('products as p', 'p.id', '=', 'd.product_id')
            ->join('suppliers as s', 's.id', '=', 'm.supplier_id')
            ->where('m.status', 'posted')
            ->selectRaw('m.reference_no, m.return_date AS entry_date, d.product_id, p.name AS product_name, d.quantity, d.total AS amount, m.reason AS detail, s.name AS source');
    }
}
