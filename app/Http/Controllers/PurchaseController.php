<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelRequest;
use App\Http\Requests\InvoiceEmailRequest;
use App\Http\Requests\PurchaseRequest;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\InvoiceService;
use App\Services\ProductService;
use App\Services\PurchaseService;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function __construct(private PurchaseService $service, private InvoiceService $invoices)
    {
        $this->accessResource('purchases');
    }

    public function index(Request $request)
    {
        return view('purchases.index', [
            'purchases' => $this->service->paginate($this->resolveIds($request->only('search', 'party_id', 'status', 'from', 'to'), ['party_id' => Supplier::class])),
            'suppliers' => Supplier::cachedActive(),
        ]);
    }

    public function create(ProductService $products)
    {
        return view('purchases.create', [
            'suppliers' => Supplier::cachedActive(),
            'oldItems' => $products->hydrateOldItems(old('items', []), 'purchase'),
        ]);
    }

    public function store(PurchaseRequest $request)
    {
        $purchase = $this->service->create($request->validated());

        return redirect()->route('purchases.show', $purchase)->with('success', "Purchase {$purchase->reference_no} saved and stock updated.");
    }

    public function show(Purchase $purchase)
    {
        return view('purchases.show', ['purchase' => $purchase->load('supplier', 'details.product.unit', 'payments', 'returns', 'creator', 'canceller')]);
    }

    public function cancel(CancelRequest $request, Purchase $purchase)
    {
        $this->service->cancel($purchase, $request->validated()['cancel_reason']);

        return back()->with('success', 'Purchase cancelled and stock reversed.');
    }

    public function pdf(Request $request, Purchase $purchase)
    {
        return $this->invoices->response($purchase->load('supplier'), $request->boolean('download'));
    }

    public function email(InvoiceEmailRequest $request, Purchase $purchase)
    {
        $data = $request->validated();

        try {
            $this->invoices->email($purchase->load('supplier'), $data['email'], $data['message'] ?? null);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'The email could not be sent. Please check the mail settings. (' . $e->getMessage() . ')');
        }

        return back()->with('success', "Invoice sent to {$data['email']}.");
    }
}
