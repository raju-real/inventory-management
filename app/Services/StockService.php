<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The single place where stock changes. Every change writes a stock transaction and
 * updates products.current_stock in the same step. Call inside DB::transaction().
 */
class StockService extends BaseService
{
    public function in(int $productId, float $quantity, string $type, Model $reference, $date, ?float $unitCost = null, ?string $remarks = null): void
    {
        $this->move($productId, $quantity, 0, $type, $reference, $date, $unitCost, $remarks);
    }

    public function out(int $productId, float $quantity, string $type, Model $reference, $date, ?float $unitCost = null, ?string $remarks = null): void
    {
        $this->move($productId, 0, $quantity, $type, $reference, $date, $unitCost, $remarks);
    }

    /** History row that does not change stock, e.g. a customer return received in damaged condition. */
    public function memo(int $productId, string $type, Model $reference, $date, string $remarks): void
    {
        $this->move($productId, 0, 0, $type, $reference, $date, null, $remarks);
    }

    /**
     * Locks every product of a document in ID order before any of them changes. Two documents
     * sharing products then queue up instead of deadlocking, whatever order their lines are in.
     */
    public function lockProducts($productIds): void
    {
        Product::whereIn('id', collect($productIds)->unique()->values())->orderBy('id')->lockForUpdate()->get(['id']);
    }

    private function move(int $productId, float $in, float $out, string $type, Model $reference, $date, ?float $unitCost, ?string $remarks): void
    {
        $product = Product::whereKey($productId)->lockForUpdate()->firstOrFail();
        $available = (float) $product->current_stock;

        if ($out > 0 && round($out, 3) > round($available, 3)) {
            throw ValidationException::withMessages([
                'stock' => "Insufficient stock for {$product->name}. Available: " . qty($available) . ', required: ' . qty($out) . '.',
            ]);
        }

        $product->current_stock = round($available + $in - $out, 3);
        $product->save();

        StockTransaction::create([
            'product_id' => $product->id,
            'transaction_date' => $date,
            'transaction_type' => $type,
            'reference_type' => class_basename($reference),
            'reference_id' => $reference->getKey(),
            'reference_no' => $reference->reference_no ?? $reference->sku ?? null,
            'quantity_in' => $in,
            'quantity_out' => $out,
            'unit_cost' => $unitCost ?? (float) $product->purchase_price,
            'remarks' => $remarks,
            'created_by' => auth()->id(),
        ]);
    }

    public function overview(array $filters)
    {
        return StockTransaction::rememberCache('overview:' . $this->listKey($filters), function () use ($filters) {
            $query = $this->overviewQuery();

            if (! empty($filters['search'])) {
                $search = '%' . $filters['search'] . '%';
                $query->where(fn ($q) => $q->where('products.name', 'like', $search)
                    ->orWhere('products.sku', 'like', $search)
                    ->orWhere('products.barcode', 'like', $search));
            }

            if (! empty($filters['category_id'])) {
                $query->where('products.category_id', $filters['category_id']);
            }

            if (! empty($filters['low_stock'])) {
                $query->where('products.min_stock', '>', 0)->whereColumn('products.current_stock', '<=', 'products.min_stock');
            }

            return $query->orderBy('products.name')->paginate(static::PER_PAGE)->withQueryString();
        });
    }

    public function summaryFor(Product $product): object
    {
        return $this->overviewQuery()->where('products.id', $product->id)->first();
    }

    public function ledger(array $filters)
    {
        return StockTransaction::rememberCache('ledger:' . $this->listKey($filters), function () use ($filters) {
            $query = StockTransaction::with('product.unit', 'creator')->orderByDesc('id');

            if (! empty($filters['product_id'])) {
                $query->where('product_id', $filters['product_id']);
            }
            if (! empty($filters['type'])) {
                $query->where('transaction_type', $filters['type']);
            }
            if (! empty($filters['from'])) {
                $query->whereDate('transaction_date', '>=', $filters['from']);
            }
            if (! empty($filters['to'])) {
                $query->whereDate('transaction_date', '<=', $filters['to']);
            }
            if (! empty($filters['search'])) {
                $query->where('reference_no', 'like', '%' . $filters['search'] . '%');
            }

            return $query->paginate(static::PER_PAGE)->withQueryString();
        });
    }

    private function overviewQuery()
    {
        $totals = StockTransaction::query()
            ->select('product_id')
            ->selectRaw("SUM(CASE WHEN transaction_type = 'opening' THEN quantity_in ELSE 0 END) AS opening_qty")
            ->selectRaw("SUM(CASE WHEN transaction_type = 'purchase' THEN quantity_in WHEN transaction_type = 'purchase_cancel' THEN -quantity_out ELSE 0 END) AS purchased_qty")
            ->selectRaw("SUM(CASE WHEN transaction_type = 'sale' THEN quantity_out WHEN transaction_type = 'sale_cancel' THEN -quantity_in ELSE 0 END) AS sold_qty")
            ->selectRaw("SUM(CASE WHEN transaction_type = 'sales_return' THEN quantity_in WHEN transaction_type = 'sales_return_cancel' THEN -quantity_out ELSE 0 END) AS customer_returned_qty")
            ->selectRaw("SUM(CASE WHEN transaction_type = 'purchase_return' THEN quantity_out WHEN transaction_type = 'purchase_return_cancel' THEN -quantity_in ELSE 0 END) AS supplier_returned_qty")
            ->selectRaw("SUM(CASE WHEN transaction_type = 'damage' THEN quantity_out WHEN transaction_type = 'damage_cancel' THEN -quantity_in ELSE 0 END) AS damaged_qty")
            ->selectRaw("SUM(CASE WHEN transaction_type IN ('adjustment', 'adjustment_cancel') THEN quantity_in - quantity_out ELSE 0 END) AS adjusted_qty")
            ->groupBy('product_id');

        return Product::with('category', 'unit')
            ->leftJoinSub($totals, 'totals', 'totals.product_id', '=', 'products.id')
            ->select('products.*', 'totals.opening_qty', 'totals.purchased_qty', 'totals.sold_qty', 'totals.customer_returned_qty',
                'totals.supplier_returned_qty', 'totals.damaged_qty', 'totals.adjusted_qty');
    }
}
