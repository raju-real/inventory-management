<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelRequest;
use App\Http\Requests\CustomerPaymentRequest;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Sale;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class CustomerPaymentController extends Controller
{
    public function __construct(private PaymentService $service)
    {
        $this->accessResource('customer-payments');
    }

    public function index(Request $request)
    {
        return view('payments.customer-index', ['payments' => $this->service->paginateCustomer($request->only('search', 'status', 'from', 'to'))]);
    }

    public function create(Request $request)
    {
        $sale = $request->filled('sale_id') ? Sale::with('customer')->where('unique_id', $request->query('sale_id'))->firstOrFail() : null;

        return view('payments.customer-form', ['customers' => Customer::cachedActive(), 'sale' => $sale, 'customerId' => $sale?->customer->unique_id ?? $request->query('customer_id')]);
    }

    public function store(CustomerPaymentRequest $request)
    {
        $payment = $this->service->createCustomerPayment($request->validated());

        return redirect($payment->sale_id ? route('sales.show', $payment->sale) : route('customer-payments.index'))
            ->with('success', "Payment {$payment->reference_no} recorded.");
    }

    public function cancel(CancelRequest $request, CustomerPayment $customerPayment)
    {
        $this->service->cancelCustomerPayment($customerPayment, $request->validated()['cancel_reason']);

        return back()->with('success', 'Payment cancelled.');
    }
}
