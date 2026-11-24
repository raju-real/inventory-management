@extends('layouts.app')
@section('title', $product->name)
@section('content')
<x-page-header :title="$product->name" :sub="$product->sku . ($product->barcode ? ' · ' . $product->barcode : '')">
    <a class="btn btn-light" href="{{ route('products.index') }}">Back</a>
    <a class="btn btn-outline-primary" href="{{ route('products.labels', $product) }}"><i class="bi bi-upc me-1"></i>Print Labels</a>
    @access('products', 'edit')<a class="btn btn-primary" href="{{ route('products.edit', $product) }}"><i class="bi bi-pencil me-1"></i>Edit</a>@endaccess
</x-page-header>
<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card h-100"><div class="card-body">
            <div class="d-flex gap-3 mb-3">
                @if ($product->image_url)<img class="thumb" style="width:84px;height:84px" src="{{ $product->image_url }}" alt="">@else<span class="thumb" style="width:84px;height:84px"><i class="bi bi-box fs-3"></i></span>@endif
                <div>
                    <div class="stat-label">Available stock</div>
                    <div class="stat-value {{ $product->is_low_stock ? 'out' : '' }}">{{ qty($product->current_stock) }} <small class="fs-6 text-muted">{{ $product->unit?->name }}</small></div>
                    @if ($product->is_low_stock)<span class="badge tone-red">Low stock</span>@endif
                    <x-status :active="$product->status" />
                </div>
            </div>
            <table class="table table-sm">
                <tr><td class="text-muted">Category</td><td>{{ $product->category?->name }}</td></tr>
                <tr><td class="text-muted">Purchase price</td><td>{{ money($product->purchase_price) }}</td></tr>
                <tr><td class="text-muted">Sales price</td><td>{{ money($product->sales_price) }}</td></tr>
                <tr><td class="text-muted">VAT</td><td>{{ $product->vat_label }}</td></tr>
                <tr><td class="text-muted">Minimum stock</td><td>{{ qty($product->min_stock) }}</td></tr>
            </table>
            @if ($product->description)<p class="small text-muted mt-2 mb-0">{{ $product->description }}</p>@endif
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card h-100"><div class="card-header">Stock summary</div><div class="card-body">
            <div class="row g-3">
                @foreach ([
                    'Opening' => $summary->opening_qty, 'Purchased' => $summary->purchased_qty, 'Customer returns' => $summary->customer_returned_qty,
                    'Sold' => $summary->sold_qty, 'Supplier returns' => $summary->supplier_returned_qty, 'Damaged' => $summary->damaged_qty, 'Adjustments' => $summary->adjusted_qty,
                ] as $label => $value)
                    <div class="col-6 col-md-3"><div class="stat-label">{{ $label }}</div><div class="fw-semibold fs-5">{{ qty($value) }}</div></div>
                @endforeach
            </div>
        </div></div>
    </div>
</div>
<div class="card">
    <div class="card-header">Stock movement history</div>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Date</th><th>Type</th><th>Reference</th><th class="text-end">In</th><th class="text-end">Out</th><th>Remarks</th><th>By</th></tr></thead>
            <tbody>
            @forelse ($movements as $row)
                <tr>
                    <td>{{ $row->transaction_date->format('d M Y') }}</td><td>{{ $row->type_label }}</td><td>{{ $row->reference_no }}</td>
                    <td class="num in">{{ (float) $row->quantity_in ? qty($row->quantity_in) : '' }}</td>
                    <td class="num out">{{ (float) $row->quantity_out ? qty($row->quantity_out) : '' }}</td>
                    <td>{{ $row->remarks }}</td><td>{{ $row->creator?->name }}</td>
                </tr>
            @empty
                <x-empty-row :cols="7" text="No stock movement yet." />
            @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :items="$movements" />
</div>
@endsection
