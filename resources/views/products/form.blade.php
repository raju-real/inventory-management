@extends('layouts.app')
@section('title', $product->exists ? 'Edit Product' : 'Add Product')
@section('content')
<x-page-header :title="$product->exists ? 'Edit Product' : 'Add Product'">
    <a class="btn btn-light" href="{{ route('products.index') }}">Back</a>
</x-page-header>
<form method="POST" enctype="multipart/form-data" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}" class="needs-validation" novalidate>
    @csrf
    @if ($product->exists) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card"><div class="card-body">
                <div class="row">
                    <div class="col-12"><x-input name="name" label="Product name" placeholder="e.g. Cotton T-shirt" :value="$product->name" required maxlength="255" /></div>
                    <div class="col-md-6"><x-input name="sku" label="Product code / SKU" placeholder="Leave blank to generate automatically" :value="$product->sku" maxlength="60" pattern="[A-Za-z0-9._\-\/]+" feedback="Use letters, numbers, dot, dash or slash only." /></div>
                    <div class="col-md-6">
                        <x-input name="barcode" label="Barcode" placeholder="Scan the product or type the barcode" :value="$product->barcode" maxlength="60" pattern="[\x21-\x7E]+" autocomplete="off" data-scan-keep help="Optional. Just scan: the code fills in here." feedback="The barcode cannot contain spaces." />
                    </div>
                    <div class="col-md-6"><x-select name="category_id" label="Category" :options="$categories->pluck('name', 'unique_id')" :value="$product->category?->unique_id" required /></div>
                    <div class="col-md-6"><x-select name="unit_id" label="Unit" :options="$units->pluck('name', 'unique_id')" :value="$product->unit?->unique_id" required /></div>
                    <div class="col-md-6"><x-input name="purchase_price" type="number" label="Purchase price" placeholder="0.00" :value="$product->purchase_price" required min="0" step="any" /></div>
                    <div class="col-md-6"><x-input name="sales_price" type="number" label="Sales price" placeholder="0.00" :value="$product->sales_price" required min="0" step="any" /></div>
                    <div class="col-md-6"><x-select name="vat_type" label="VAT type" :options="['percentage' => 'Percentage (%)', 'flat' => 'Flat amount per unit']" :placeholder="false" :value="$product->vat_type ?? 'percentage'" /></div>
                    <div class="col-md-6"><x-input name="vat_value" type="number" label="VAT" placeholder="e.g. 15" :value="$product->exists ? (float) $product->vat_value : null" min="0" step="any" help="Used on every sale of this product. It can still be changed on the sale." /></div>
                    <div class="col-md-6"><x-input name="min_stock" type="number" label="Minimum stock alert" placeholder="e.g. 10" :value="$product->exists ? (float) $product->min_stock : null" min="0" step="any" help="A low-stock alert shows at or below this quantity." /></div>
                    <div class="col-md-6">
                        @if ($product->exists)
                            <x-input name="opening_stock_display" label="Opening stock" placeholder="Opening stock" :value="qty($product->opening_stock)" disabled help="Use Stock Adjustments to correct stock." />
                        @else
                            <x-input name="opening_stock" type="number" label="Opening stock" placeholder="e.g. 100" min="0" step="any" />
                        @endif
                    </div>
                    <div class="col-12"><x-textarea name="description" label="Description" placeholder="Product description (optional)" :value="$product->description" maxlength="2000" /></div>
                </div>
            </div></div>
        </div>
        <div class="col-lg-4">
            <div class="card"><div class="card-body">
                <x-image-input label="Product image" :current="$product->image_url" />
                <x-select name="status" label="Status" :options="[1 => 'Active', 0 => 'Inactive']" :placeholder="false" :value="(int) $product->status" required />
                <div class="form-actions"><button class="btn btn-primary btn-submit" type="submit">Save Product</button></div>
            </div></div>
        </div>
    </div>
</form>
@endsection
@push('scripts')
<script>
    // Wherever the cursor is, a scan on this screen fills the barcode field.
    window.Scanner.on(function (code) {
        const input = document.getElementById('barcode');
        input.value = code;
        input.classList.add('is-valid');
        window.Scanner.beep(true);
    });
</script>
@endpush
