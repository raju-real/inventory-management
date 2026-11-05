<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelRequest;
use App\Http\Requests\SalesReturnRequest;
use App\Models\Sale;
use App\Models\SalesReturn;
use App\Services\SalesReturnService;
use Illuminate\Http\Request;

class SalesReturnController extends Controller
{
    public function __construct(private SalesReturnService $service)
    {
        $this->accessResource('sales-returns');
    }

    public function index(Request $request)
    {
        return view('sales-returns.index', ['returns' => $this->service->paginate($request->only('search', 'status', 'from', 'to'))]);
    }

    public function create(Request $request)
    {
        $invoice = trim((string) $request->query('invoice'));
        $sale = $invoice === '' ? null : Sale::with('customer', 'details.product.unit')->where('reference_no', $invoice)->first();

        return view('sales-returns.create', [
            'invoice' => $invoice,
            'sale' => $sale,
            'notFound' => $invoice !== '' && ! $sale,
            'recent' => Sale::posted()->latest('id')->limit(30)->pluck('reference_no'),
        ]);
    }

    public function store(SalesReturnRequest $request)
    {
        $return = $this->service->create($request->validated());

        return redirect()->route('sales-returns.show', $return)->with('success', "Sales return {$return->reference_no} saved.");
    }

    public function show(SalesReturn $salesReturn)
    {
        return view('sales-returns.show', ['return' => $salesReturn->load('sale', 'customer', 'details.product.unit', 'creator', 'canceller')]);
    }

    public function cancel(CancelRequest $request, SalesReturn $salesReturn)
    {
        $this->service->cancel($salesReturn, $request->validated()['cancel_reason']);

        return back()->with('success', 'Sales return cancelled.');
    }
}
