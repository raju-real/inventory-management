<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Services\CustomerService;
use App\Services\PointService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(private CustomerService $service)
    {
        $this->accessResource('customers');
    }

    public function index(Request $request)
    {
        return view('customers.index', ['customers' => $this->service->paginate($request->only('search', 'status'))]);
    }

    public function create()
    {
        return view('customers.form', ['customer' => new Customer(['status' => true])]);
    }

    public function store(CustomerRequest $request)
    {
        $customer = $this->service->create($request->validated());

        // The + button on the invoice screens adds a customer without leaving the page.
        if ($request->expectsJson()) {
            return response()->json([
                'id' => $customer->unique_id,
                'name' => $customer->name,
                'label' => $customer->name . ($customer->mobile ? ' (' . $customer->mobile . ')' : ''),
            ], 201);
        }

        return redirect()->route('customers.show', $customer)->with('success', 'Customer created.');
    }

    public function show(Customer $customer)
    {
        return view('customers.show', [
            'customer' => $customer,
            'sales' => $customer->sales()->latest('id')->paginate(10, ['*'], 'sales_page'),
            'returns' => $customer->salesReturns()->with('sale')->latest('id')->paginate(10, ['*'], 'returns_page'),
            'pointHistory' => $customer->pointTransactions()->with('sale')->latest('id')->paginate(10, ['*'], 'points_page'),
            'pointValue' => app(PointService::class)->settings()['point_value'],
        ]);
    }

    public function edit(Customer $customer)
    {
        return view('customers.form', compact('customer'));
    }

    public function update(CustomerRequest $request, Customer $customer)
    {
        $this->service->update($customer, $request->validated());

        return redirect()->route('customers.show', $customer)->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer)
    {
        $this->service->delete($customer);

        return redirect()->route('customers.index')->with('success', 'Customer deleted.');
    }
}
