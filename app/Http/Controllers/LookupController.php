<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;

/** Search-as-you-type options for the Select2 filters: customers, suppliers and products. */
class LookupController extends Controller
{
    private const PER_PAGE = 20;

    /** A user may search a list if any page they can open filters by it. */
    private const ACCESS = [
        'customers' => ['customers', 'sales', 'sales-returns', 'customer-payments', 'report-sales', 'report-customer-ledger', 'report-due-list'],
        'suppliers' => ['suppliers', 'purchases', 'purchase-returns', 'supplier-payments', 'report-purchases', 'report-supplier-ledger', 'report-due-list'],
        'products' => ['products', 'stock', 'stock-ledger', 'stock-adjustments', 'damages', 'sales', 'purchases', 'report-stock', 'report-low-stock', 'report-damage-returns'],
    ];

    public function __invoke(Request $request, string $type)
    {
        abort_unless(array_key_exists($type, self::ACCESS), 404);
        abort_unless(collect(self::ACCESS[$type])->contains(fn ($menu) => $request->user()->canAccess($menu)), 403);

        $term = trim((string) $request->query('q', ''));
        $like = '%' . $term . '%';

        if ($type === 'products') {
            $query = Product::query()->select('id', 'unique_id', 'name', 'sku', 'barcode', 'status')
                ->when($term !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('sku', 'like', $like)->orWhere('barcode', 'like', $like)));
        } else {
            $model = $type === 'customers' ? Customer::class : Supplier::class;
            $query = $model::query()->select('id', 'unique_id', 'name', 'mobile', 'status')
                ->when($term !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('mobile', 'like', $like)));
        }

        $page = $query->orderByDesc('status')->orderBy('name')->simplePaginate(self::PER_PAGE);

        return response()->json([
            'results' => collect($page->items())->map(fn ($item) => self::option($type, $item))->all(),
            'pagination' => ['more' => $page->hasMorePages()],
        ]);
    }

    /** Select2 option: id is the public unique_id; "sub" is the mobile or product code shown under the name. */
    public static function option(string $type, $item): array
    {
        $sub = $type === 'products' ? $item->sku : $item->mobile;

        return [
            'id' => $item->unique_id,
            'text' => $item->name . ($sub ? ' (' . $sub . ')' : ''),
            'name' => $item->name,
            'sub' => $sub,
            'inactive' => ! $item->status,
        ];
    }

    /** The text of the option that is already chosen in a filter, so the page shows it before any search. */
    public static function selected(string $type, ?string $uniqueId): ?array
    {
        if (! $uniqueId) {
            return null;
        }
        $model = ['customers' => Customer::class, 'suppliers' => Supplier::class, 'products' => Product::class][$type] ?? null;
        $item = $model ? $model::where('unique_id', $uniqueId)->first() : null;

        return $item ? self::option($type, $item) : null;
    }
}
