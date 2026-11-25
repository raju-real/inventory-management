<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\LedgerService;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private ReportService $service, private LedgerService $ledger)
    {
        $this->middleware('access:report-stock,view')->only('stock');
        $this->middleware('access:report-low-stock,view')->only('lowStock');
        $this->middleware('access:report-purchases,view')->only('purchases');
        $this->middleware('access:report-sales,view')->only('sales');
        $this->middleware('access:report-damage-returns,view')->only('damageReturns');
        $this->middleware('access:report-customer-ledger,view')->only('customerLedger');
        $this->middleware('access:report-supplier-ledger,view')->only('supplierLedger');
    }

    public function stock(Request $request)
    {
        return view('reports.stock', [
            'title' => 'Stock Report',
            'route' => 'reports.stock',
            'products' => $this->service->stock($this->resolveIds($request->only('search', 'category_id'), ['category_id' => Category::class])),
            'categories' => Category::cachedActive(),
        ]);
    }

    public function lowStock(Request $request)
    {
        return view('reports.stock', [
            'title' => 'Low Stock Report',
            'route' => 'reports.low-stock',
            'products' => $this->service->lowStock($this->resolveIds($request->only('search', 'category_id'), ['category_id' => Category::class])),
            'categories' => Category::cachedActive(),
        ]);
    }

    public function purchases(Request $request)
    {
        $filters = $this->dateFilters($request, ['party_id', 'group']);

        return view('reports.invoices', $this->service->purchases($this->resolveIds($filters, ['party_id' => Supplier::class])) + [
            'title' => 'Purchase Report',
            'route' => 'reports.purchases',
            'partyLabel' => 'Supplier',
            'group' => $filters['group'] ?? 'invoice',
        ]);
    }

    public function sales(Request $request)
    {
        $filters = $this->dateFilters($request, ['party_id', 'group']);

        return view('reports.invoices', $this->service->sales($this->resolveIds($filters, ['party_id' => Customer::class])) + [
            'title' => 'Sales Report',
            'route' => 'reports.sales',
            'partyLabel' => 'Customer',
            'group' => $filters['group'] ?? 'invoice',
        ]);
    }

    public function damageReturns(Request $request)
    {
        $type = in_array($request->query('type'), ['sales_return', 'purchase_return'], true) ? $request->query('type') : 'damage';

        return view('reports.damage-returns', $this->service->damageReturns($type, $this->resolveIds($this->dateFilters($request, ['product_id']), ['product_id' => Product::class])) + [
            'type' => $type,
        ]);
    }

    public function customerLedger(Request $request)
    {
        $filters = $this->dateFilters($request, ['party_id']);
        $customer = empty($filters['party_id']) ? null : Customer::where('unique_id', $filters['party_id'])->firstOrFail();

        return view('reports.ledger', [
            'title' => 'Customer Ledger',
            'route' => 'reports.customer-ledger',
            'partyLabel' => 'Customer',
            'party' => $customer,
            'ledger' => $customer ? $this->ledger->customer($customer, $filters) : null,
            'balanceHint' => 'Positive balance: the customer owes you.',
        ]);
    }

    public function supplierLedger(Request $request)
    {
        $filters = $this->dateFilters($request, ['party_id']);
        $supplier = empty($filters['party_id']) ? null : Supplier::where('unique_id', $filters['party_id'])->firstOrFail();

        return view('reports.ledger', [
            'title' => 'Supplier Ledger',
            'route' => 'reports.supplier-ledger',
            'partyLabel' => 'Supplier',
            'party' => $supplier,
            'ledger' => $supplier ? $this->ledger->supplier($supplier, $filters) : null,
            'balanceHint' => 'Positive balance: you owe the supplier.',
        ]);
    }

    private function dateFilters(Request $request, array $extra): array
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return $request->only(array_merge(['from', 'to'], $extra));
    }
}
