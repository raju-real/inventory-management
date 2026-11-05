@extends('layouts.app')
@section('title', 'New Stock Adjustment')
@section('content')
<x-page-header title="New Stock Adjustment"><a class="btn btn-light" href="{{ route('stock-adjustments.index') }}">Back</a></x-page-header>
<x-form-errors />
<div class="card" style="max-width:640px"><div class="card-body">
    <form id="adjustment-form" method="POST" action="{{ route('stock-adjustments.store') }}" class="needs-validation" novalidate autocomplete="off"
          data-search-url="{{ route('products.search') }}" data-lookup-url="{{ route('products.lookup') }}" data-old-product="{{ json_encode($oldProduct) }}">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="product-search">Product <span class="req">*</span></label>
            <div class="picker">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                    <input class="form-control" id="product-search" placeholder="Scan a barcode, or type product name / code" autocomplete="off">
                </div>
            </div>
            <input type="hidden" name="product_id" id="product_id" value="{{ old('product_id') }}">
            <div id="picked-product" class="alert alert-light border mt-2 mb-0 d-none"></div>
        </div>
        <x-input name="adjustment_date" type="date" label="Adjustment date" placeholder="Select date" :value="now()->toDateString()" required />
        <div class="row">
            <div class="col-md-6"><x-select name="type" label="Adjustment type" :options="['increase' => 'Increase stock', 'decrease' => 'Decrease stock']" required /></div>
            <div class="col-md-6"><x-input name="quantity" type="number" label="Quantity" placeholder="e.g. 5" required min="0.001" step="any" /></div>
        </div>
        <x-input name="reason" label="Reason" placeholder="e.g. Physical stock count correction" required maxlength="255" />
        <div class="form-actions"><button class="btn btn-primary btn-submit" type="submit">Save Adjustment</button></div>
    </form>
</div></div>
@endsection
@push('scripts')
<script src="{{ asset('assets/js/picker.js') }}"></script>
<script>
    (function () {
        const form = document.getElementById('adjustment-form');
        const box = document.getElementById('picked-product');

        function pick(product, fromScan) {
            document.getElementById('product_id').value = product.id;
            box.textContent = product.name + ' (' + product.sku + ') - available: ' + product.available + ' ' + (product.unit || '');
            box.classList.remove('d-none');
            if (fromScan) {
                window.Scanner.beep(true);
            }
        }

        window.ProductPicker({
            input: document.getElementById('product-search'),
            context: 'adjustment',
            searchUrl: form.dataset.searchUrl,
            lookupUrl: form.dataset.lookupUrl,
            onPick: pick,
        });

        const old = JSON.parse(form.dataset.oldProduct);
        if (old) {
            pick(old, false);
        }

        form.addEventListener('form:validate', function (event) {
            if (!document.getElementById('product_id').value) {
                event.preventDefault();
                window.Toast('Scan or choose a product first.', 'warning');
            }
        });
        form.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && event.target.tagName === 'INPUT' && !event.defaultPrevented) {
                event.preventDefault();
            }
        });
    })();
</script>
@endpush
