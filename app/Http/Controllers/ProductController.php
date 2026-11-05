<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Services\BarcodeService;
use App\Services\ProductService;
use App\Services\StockService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /** Which screen uses the product picker => the menu permission it needs. */
    private const PICKER_CONTEXTS = [
        'purchase' => 'purchases',
        'sale' => 'sales',
        'damage' => 'damages',
        'adjustment' => 'stock-adjustments',
    ];

    public function __construct(private ProductService $service, private StockService $stock)
    {
        $this->accessResource('products');
    }

    public function index(Request $request)
    {
        return view('products.index', [
            'products' => $this->service->paginate($this->resolveIds($request->only('search', 'status', 'category_id', 'low_stock'), ['category_id' => Category::class])),
            'categories' => Category::cachedActive(),
        ]);
    }

    public function create(Request $request)
    {
        return view('products.form', [
            'product' => new Product(['status' => true, 'barcode' => $request->query('barcode')]),
            'categories' => Category::cachedActive(),
            'units' => Unit::cachedActive(),
        ]);
    }

    public function store(ProductRequest $request)
    {
        $product = $this->service->create($request->validated());

        return redirect()->route('products.show', $product)->with('success', 'Product created.');
    }

    public function show(Product $product)
    {
        return view('products.show', [
            'product' => $product->load('category', 'unit'),
            'summary' => $this->stock->summaryFor($product),
            'movements' => $product->stockTransactions()->with('creator')->latest('id')->paginate(10),
        ]);
    }

    public function edit(Product $product)
    {
        return view('products.form', [
            'product' => $product,
            'categories' => Category::cachedActive(),
            'units' => Unit::cachedActive(),
        ]);
    }

    public function update(ProductRequest $request, Product $product)
    {
        $this->service->update($product, $request->validated());

        return redirect()->route('products.show', $product)->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $this->service->delete($product);

        return redirect()->route('products.index')->with('success', 'Product deleted.');
    }

    public function labels(Request $request, Product $product, BarcodeService $barcode)
    {
        $data = $request->validate([
            'copies' => ['nullable', 'integer', 'min:1', 'max:200'],
            'show_price' => ['nullable', 'boolean'],
        ]);

        return view('products.labels', [
            'product' => $product,
            'copies' => $data['copies'] ?? 1,
            'showPrice' => $data['show_price'] ?? true,
            'barcodeSvg' => $barcode->svg($product->label_code),
        ]);
    }

    /** Autocomplete: GET products-search?q=shirt&context=sale */
    public function search(Request $request)
    {
        $context = $this->authorizePicker($request);
        $term = trim((string) $request->query('q'));

        if ($term === '') {
            return response()->json([]);
        }

        return response()->json($this->service->search($term)->map(fn ($product) => $this->service->toPicker($product, $context))->values());
    }

    /** Barcode scan: GET products-lookup?code=123&context=sale */
    public function lookup(Request $request)
    {
        $context = $this->authorizePicker($request);
        $product = $this->service->findByCode(trim((string) $request->query('code')));

        if (! $product) {
            return response()->json(['message' => 'No active product found for this code.'], 404);
        }

        return response()->json($this->service->toPicker($product, $context));
    }

    private function authorizePicker(Request $request): string
    {
        $context = (string) $request->query('context');

        abort_unless(isset(self::PICKER_CONTEXTS[$context]), 422, 'Unknown context.');
        abort_unless($request->user()->canAccess(self::PICKER_CONTEXTS[$context], 'create'), 403);

        return $context;
    }
}
