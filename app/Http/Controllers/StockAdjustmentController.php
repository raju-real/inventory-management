<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelRequest;
use App\Http\Requests\StockAdjustmentRequest;
use App\Models\StockAdjustment;
use App\Services\ProductService;
use App\Services\StockAdjustmentService;
use Illuminate\Http\Request;

class StockAdjustmentController extends Controller
{
    public function __construct(private StockAdjustmentService $service)
    {
        $this->accessResource('stock-adjustments');
    }

    public function index(Request $request)
    {
        return view('stock-adjustments.index', ['adjustments' => $this->service->paginate($request->only('search', 'type'))]);
    }

    public function create(ProductService $products)
    {
        $old = old('product_id') ? $products->hydrateOldItems([['product_id' => old('product_id')]], 'adjustment') : [];

        return view('stock-adjustments.create', ['oldProduct' => $old[0] ?? null]);
    }

    public function store(StockAdjustmentRequest $request)
    {
        $adjustment = $this->service->create($request->validated());

        return redirect()->route('stock-adjustments.index')->with('success', "Adjustment {$adjustment->reference_no} saved and stock updated.");
    }

    public function cancel(CancelRequest $request, StockAdjustment $stockAdjustment)
    {
        $this->service->cancel($stockAdjustment, $request->validated()['cancel_reason']);

        return back()->with('success', 'Adjustment cancelled and stock reversed.');
    }
}
