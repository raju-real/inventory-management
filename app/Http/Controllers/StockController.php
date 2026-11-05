<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Services\StockService;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function __construct(private StockService $service)
    {
        $this->middleware('access:stock,view')->only('index');
        $this->middleware('access:stock-ledger,view')->only('ledger');
    }

    public function index(Request $request)
    {
        return view('stock.index', [
            'products' => $this->service->overview($this->resolveIds($request->only('search', 'category_id', 'low_stock'), ['category_id' => Category::class])),
            'categories' => Category::cachedActive(),
        ]);
    }

    public function ledger(Request $request)
    {
        return view('stock.ledger', [
            'transactions' => $this->service->ledger($this->resolveIds($request->only('product_id', 'type', 'from', 'to', 'search'), ['product_id' => Product::class])),
            'products' => Product::rememberCache('options', fn () => Product::orderBy('name')->get(['id', 'unique_id', 'name', 'sku'])),
            'types' => StockTransaction::TYPES,
        ]);
    }
}
