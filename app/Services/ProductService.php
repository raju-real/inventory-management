<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductService extends BaseService
{
    public function __construct(private StockService $stock, private ImageService $images)
    {
    }

    public function paginate(array $filters)
    {
        return Product::rememberCache($this->listKey($filters), function () use ($filters) {
            $query = Product::with('category', 'unit')->latest('id');
            $this->applyStatusFilter($query, $filters);

            if (! empty($filters['search'])) {
                $search = '%' . $filters['search'] . '%';
                $query->where(fn ($q) => $q->where('name', 'like', $search)
                    ->orWhere('sku', 'like', $search)
                    ->orWhere('barcode', 'like', $search));
            }
            if (! empty($filters['category_id'])) {
                $query->where('category_id', $filters['category_id']);
            }
            if (! empty($filters['low_stock'])) {
                $query->where('min_stock', '>', 0)->whereColumn('current_stock', '<=', 'min_stock');
            }

            return $query->paginate(static::PER_PAGE)->withQueryString();
        });
    }

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $data = $this->images->resolve($data, null, 'products');
            $opening = (float) ($data['opening_stock'] ?? 0);

            $product = Product::create(array_merge($data, [
                'sku' => $data['sku'] ?? 'TMP-' . uniqid(),
                'opening_stock' => $opening,
                'current_stock' => 0,
                'min_stock' => $data['min_stock'] ?? 0,
                'created_by' => auth()->id(),
            ]));

            if (empty($data['sku'])) {
                $product->sku = 'PRD-' . str_pad($product->id, 6, '0', STR_PAD_LEFT);
                $product->save();
            }

            if ($opening > 0) {
                $this->stock->in($product->id, $opening, 'opening', $product, now()->toDateString(), (float) $product->purchase_price, 'Opening stock');
            }

            return $product->refresh();
        }, 3);
    }

    public function update(Product $product, array $data): Product
    {
        // Stock figures only ever change through stock transactions.
        unset($data['opening_stock'], $data['current_stock']);
        $data = $this->images->resolve($data, $product, 'products');
        $data['sku'] = $data['sku'] ?? $product->sku;
        $data['min_stock'] = $data['min_stock'] ?? 0;
        $product->update($data);

        return $product;
    }

    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product) {
            if ($product->stockTransactions()->where('transaction_type', '!=', 'opening')->exists()) {
                throw ValidationException::withMessages(['product' => 'This product has stock transactions and cannot be deleted. Deactivate it instead.']);
            }

            $product->stockTransactions()->delete();
            $product->delete();
        }, 3);

        $this->images->delete($product->image);
    }

    /** Autocomplete for the transaction forms: name, SKU or barcode. */
    public function search(string $term): Collection
    {
        return Product::rememberCache('search:' . md5($term), function () use ($term) {
            $like = '%' . $term . '%';

            return Product::with('unit')->active()
                ->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('sku', 'like', $like)->orWhere('barcode', 'like', $like))
                ->orderBy('name')
                ->limit(10)
                ->get();
        });
    }

    /** Exact match used by barcode scans: barcode first, then SKU. */
    public function findByCode(string $code): ?Product
    {
        return Product::rememberCache('code:' . md5($code), function () use ($code) {
            return Product::with('unit')->active()->where('barcode', $code)->first()
                ?? Product::with('unit')->active()->where('sku', $code)->first();
        });
    }

    /** What the transaction screens need to know about a product. */
    public function toPicker(Product $product, string $context): array
    {
        return [
            'id' => $product->unique_id,
            'name' => $product->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'unit' => $product->unit?->short_name ?: $product->unit?->name,
            'available' => (float) $product->current_stock,
            'price' => (float) ($context === 'purchase' ? $product->purchase_price : ($context === 'sale' ? $product->sales_price : 0)),
        ];
    }

    /** Rebuilds line items after a failed validation so the form shows them again. */
    public function hydrateOldItems(array $items, string $context): array
    {
        $products = Product::with('unit')->whereIn('unique_id', array_filter(array_column($items, 'product_id')))->get()->keyBy('unique_id');

        return collect($items)
            ->filter(fn ($item) => isset($products[$item['product_id'] ?? 0]))
            ->map(fn ($item) => array_merge($this->toPicker($products[$item['product_id']], $context), [
                'quantity' => $item['quantity'] ?? 1,
                'price' => $item['unit_price'] ?? 0,
                'reason' => $item['reason'] ?? '',
            ]))
            ->values()
            ->all();
    }
}
