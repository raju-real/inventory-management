@extends('layouts.app')
@section('title', 'Record Damage')
@section('content')
<x-page-header title="Record Damage" sub="Stock decreases when this is saved."><a class="btn btn-light" href="{{ route('damages.index') }}">Back</a></x-page-header>
<x-form-errors />
<form id="transaction-form" method="POST" action="{{ route('damages.store') }}" class="needs-validation" novalidate autocomplete="off"
      data-mode="damage" data-search-url="{{ route('products.search') }}" data-lookup-url="{{ route('products.lookup') }}" data-old-items="{{ json_encode($oldItems) }}">
    @csrf
    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card mb-3"><div class="card-body">
                <label class="form-label" for="product-search">Add product</label>
                <div class="picker">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                        <input class="form-control" id="product-search" placeholder="Scan a barcode, or type product name / code" autocomplete="off">
                    </div>
                </div>
            </div></div>
            <div class="card">
                <div class="table-responsive">
                    <table class="table line-table">
                        <thead><tr><th>Product</th><th>Available</th><th style="width:120px">Damage qty</th><th>Reason</th><th style="width:50px"></th></tr></thead>
                        <tbody id="line-items"></tbody>
                        <tbody><tr id="line-empty"><td colspan="5" class="text-center text-muted py-5"><i class="bi bi-upc-scan d-block fs-2 mb-1"></i>Scan a barcode or search for a product to begin.</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card"><div class="card-body">
                <x-input name="damage_date" type="date" label="Damage date" placeholder="Select date" :value="now()->toDateString()" required />
                <div class="summary-row"><span>Items / quantity</span><span><span id="summary-count">0</span> / <span id="summary-qty">0</span></span></div>
                <x-textarea name="notes" label="Notes" placeholder="Notes (optional)" rows="2" maxlength="1000" wrapper="mt-2 mb-3" />
                <div class="small text-muted mb-3">Recorded by {{ auth()->user()->name }}</div>
                <div class="form-actions"><button class="btn btn-primary btn-submit" type="submit"><i class="bi bi-check2-circle me-1"></i>Save Damage</button></div>
            </div></div>
        </div>
    </div>
</form>
@endsection
@push('scripts')
<script src="{{ asset('assets/js/picker.js') }}"></script>
<script src="{{ asset('assets/js/transaction.js') }}"></script>
@endpush
