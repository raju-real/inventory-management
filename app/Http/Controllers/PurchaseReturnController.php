<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelRequest;
use App\Http\Requests\PurchaseReturnRequest;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Services\PurchaseReturnService;
use Illuminate\Http\Request;

class PurchaseReturnController extends Controller
{
    public function __construct(private PurchaseReturnService $service)
    {
        $this->accessResource('purchase-returns');
    }

    public function index(Request $request)
    {
        return view('purchase-returns.index', ['returns' => $this->service->paginate($request->only('search', 'status', 'from', 'to'))]);
    }

    public function create(Request $request)
    {
        $invoice = trim((string) $request->query('invoice'));
        $purchase = $invoice === '' ? null : Purchase::with('supplier', 'details.product.unit')->where('reference_no', $invoice)->first();

        return view('purchase-returns.create', [
            'invoice' => $invoice,
            'purchase' => $purchase,
            'notFound' => $invoice !== '' && ! $purchase,
            'recent' => Purchase::posted()->latest('id')->limit(30)->pluck('reference_no'),
        ]);
    }

    public function store(PurchaseReturnRequest $request)
    {
        $return = $this->service->create($request->validated());

        return redirect()->route('purchase-returns.show', $return)->with('success', "Purchase return {$return->reference_no} saved.");
    }

    public function show(PurchaseReturn $purchaseReturn)
    {
        return view('purchase-returns.show', ['return' => $purchaseReturn->load('purchase', 'supplier', 'details.product.unit', 'creator', 'canceller')]);
    }

    public function cancel(CancelRequest $request, PurchaseReturn $purchaseReturn)
    {
        $this->service->cancel($purchaseReturn, $request->validated()['cancel_reason']);

        return back()->with('success', 'Purchase return cancelled.');
    }
}
