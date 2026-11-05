@extends('layouts.app')
@section('title', 'New Sale')
@section('content')
<x-page-header title="New Sale" sub="Stock decreases when this is saved."><a class="btn btn-light" href="{{ route('sales.index') }}">Back</a></x-page-header>
@include('invoices._form', [
    'mode' => 'sale', 'action' => route('sales.store'), 'dateField' => 'sale_date', 'partyField' => 'customer_id', 'partyLabel' => 'Customer', 'parties' => $customers,
    'partyDefault' => $customers->firstWhere('name', 'Walk-in Customer')?->unique_id,
])
@endsection
@push('scripts')
<script src="{{ asset('assets/js/picker.js') }}"></script>
<script src="{{ asset('assets/js/transaction.js') }}"></script>
@endpush
