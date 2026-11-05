<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function __construct(private SupplierService $service)
    {
        $this->accessResource('suppliers');
    }

    public function index(Request $request)
    {
        return view('suppliers.index', ['suppliers' => $this->service->paginate($request->only('search', 'status'))]);
    }

    public function create()
    {
        return view('suppliers.form', ['supplier' => new Supplier(['status' => true])]);
    }

    public function store(SupplierRequest $request)
    {
        $supplier = $this->service->create($request->validated());

        // The + button on the invoice screens adds a supplier without leaving the page.
        if ($request->expectsJson()) {
            return response()->json([
                'id' => $supplier->unique_id,
                'name' => $supplier->name,
                'label' => $supplier->name . ($supplier->mobile ? ' (' . $supplier->mobile . ')' : ''),
            ], 201);
        }

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Supplier created.');
    }

    public function show(Supplier $supplier)
    {
        return view('suppliers.show', [
            'supplier' => $supplier,
            'purchases' => $supplier->purchases()->latest('id')->paginate(10, ['*'], 'purchases_page'),
            'returns' => $supplier->purchaseReturns()->with('purchase')->latest('id')->paginate(10, ['*'], 'returns_page'),
        ]);
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.form', compact('supplier'));
    }

    public function update(SupplierRequest $request, Supplier $supplier)
    {
        $this->service->update($supplier, $request->validated());

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier)
    {
        $this->service->delete($supplier);

        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted.');
    }
}
