<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelRequest;
use App\Http\Requests\InvoiceEmailRequest;
use App\Http\Requests\SaleRequest;
use App\Models\Customer;
use App\Models\Sale;
use App\Services\InvoiceService;
use App\Services\ProductService;
use App\Services\SaleService;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(private SaleService $service, private InvoiceService $invoices)
    {
        $this->accessResource('sales');
    }

    public function index(Request $request)
    {
        return view('sales.index', [
            'sales' => $this->service->paginate($this->resolveIds($request->only('search', 'party_id', 'status', 'from', 'to'), ['party_id' => Customer::class])),
            'customers' => Customer::cachedActive(),
        ]);
    }

    public function create(ProductService $products)
    {
        return view('sales.create', [
            'customers' => Customer::cachedActive(),
            'oldItems' => $products->hydrateOldItems(old('items', []), 'sale'),
        ]);
    }

    public function store(SaleRequest $request)
    {
        $sale = $this->service->create($request->validated());

        return redirect()->route('sales.show', $sale)->with('success', "Sale {$sale->reference_no} saved and stock updated.");
    }

    public function show(Sale $sale)
    {
        return view('sales.show', ['sale' => $sale->load('customer', 'details.product.unit', 'payments', 'returns', 'creator', 'canceller')]);
    }

    public function cancel(CancelRequest $request, Sale $sale)
    {
        $this->service->cancel($sale, $request->validated()['cancel_reason']);

        return back()->with('success', 'Sale cancelled and stock reversed.');
    }

    public function pdf(Request $request, Sale $sale)
    {
        return $this->invoices->response($sale->load('customer'), $request->boolean('download'));
    }

    public function receipt(Sale $sale)
    {
        return $this->invoices->receipt($sale);
    }

    public function email(InvoiceEmailRequest $request, Sale $sale)
    {
        $data = $request->validated();

        try {
            $this->invoices->email($sale->load('customer'), $data['email'], $data['message'] ?? null);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'The email could not be sent. Please check the mail settings. (' . $e->getMessage() . ')');
        }

        return back()->with('success', "Invoice sent to {$data['email']}.");
    }
}
