@extends('layouts.app')
@section('title', 'New Purchase')
@section('content')
<x-page-header title="New Purchase" sub="Stock increases when this is saved."><a class="btn btn-light" href="{{ route('purchases.index') }}">Back</a></x-page-header>
@include('invoices._form', [
    'mode' => 'purchase', 'action' => route('purchases.store'), 'dateField' => 'purchase_date', 'partyField' => 'supplier_id', 'partyLabel' => 'Supplier', 'parties' => $suppliers,
])
@endsection
@push('scripts')
<script src="{{ asset('assets/js/picker.js') }}"></script>
<script src="{{ asset('assets/js/transaction.js') }}"></script>
@endpush
