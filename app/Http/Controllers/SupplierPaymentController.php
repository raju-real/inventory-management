<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelRequest;
use App\Http\Requests\SupplierPaymentRequest;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class SupplierPaymentController extends Controller
{
    public function __construct(private PaymentService $service)
    {
        $this->accessResource('supplier-payments');
    }

    public function index(Request $request)
    {
        return view('payments.supplier-index', ['payments' => $this->service->paginateSupplier($request->only('search', 'status', 'from', 'to'))]);
    }

    public function create(Request $request)
    {
        $purchase = $request->filled('purchase_id') ? Purchase::with('supplier')->where('unique_id', $request->query('purchase_id'))->firstOrFail() : null;

        return view('payments.supplier-form', ['suppliers' => Supplier::cachedActive(), 'purchase' => $purchase, 'supplierId' => $purchase?->supplier->unique_id ?? $request->query('supplier_id')]);
    }

    public function store(SupplierPaymentRequest $request)
    {
        $payment = $this->service->createSupplierPayment($request->validated());

        return redirect($payment->purchase_id ? route('purchases.show', $payment->purchase) : route('supplier-payments.index'))
            ->with('success', "Payment {$payment->reference_no} recorded.");
    }

    public function cancel(CancelRequest $request, SupplierPayment $supplierPayment)
    {
        $this->service->cancelSupplierPayment($supplierPayment, $request->validated()['cancel_reason']);

        return back()->with('success', 'Payment cancelled.');
    }
}
