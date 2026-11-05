@extends('layouts.app')
@section('title', 'Products')
@section('content')
<x-page-header title="Products" sub="Scan a barcode on this page to find a product.">
    @access('products', 'create')<a class="btn btn-primary btn-submit" href="{{ route('products.create') }}"><i class="bi bi-plus-lg me-1"></i>Add Product</a>@endaccess
</x-page-header>
<x-filter search="Search by name, code or barcode" scan>
    <x-filter-select name="category_id" label="Category" :options="$categories->pluck('name', 'unique_id')" />
    <x-filter-select name="status" label="Status" :options="[1 => 'Active', 0 => 'Inactive']" />
    <x-filter-select name="low_stock" label="Stock" :options="[1 => 'Low stock only']" />
</x-filter>
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Product</th><th>Code / Barcode</th><th>Category</th><th class="text-end">Purchase</th><th class="text-end">Sales</th><th class="text-end">Stock</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($products as $product)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            @if ($product->image_url)<img class="thumb" src="{{ $product->image_url }}" alt="">@else<span class="thumb"><i class="bi bi-box"></i></span>@endif
                            <a class="fw-medium text-decoration-none" href="{{ route('products.show', $product) }}">{{ $product->name }}</a>
                        </div>
                    </td>
                    <td>{{ $product->sku }}<div class="small text-muted">{{ $product->barcode }}</div></td>
                    <td>{{ $product->category?->name }}</td>
                    <td class="num">{{ number_format($product->purchase_price, 2) }}</td>
                    <td class="num">{{ number_format($product->sales_price, 2) }}</td>
                    <td class="num">
                        <span class="{{ $product->is_low_stock ? 'out' : '' }}">{{ qty($product->current_stock) }}</span> <small class="text-muted">{{ $product->unit?->short_name }}</small>
                        @if ($product->is_low_stock)<div><span class="badge tone-red">Low</span></div>@endif
                    </td>
                    <td><x-status :active="$product->status" /></td>
                    <td class="table-actions">
                        <a class="btn btn-sm btn-light" title="Print barcode labels" href="{{ route('products.labels', $product) }}"><i class="bi bi-upc"></i></a>
                        @access('products', 'edit')<a class="btn btn-sm btn-light" title="Edit" href="{{ route('products.edit', $product) }}"><i class="bi bi-pencil"></i></a>@endaccess
                        @access('products', 'delete')<button class="btn btn-sm btn-light text-danger" title="Delete" data-delete-url="{{ route('products.destroy', $product) }}" data-name="{{ $product->name }}"><i class="bi bi-trash"></i></button>@endaccess
                    </td>
                </tr>
            @empty
                <x-empty-row :cols="8" />
            @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :items="$products" />
</div>
@endsection
