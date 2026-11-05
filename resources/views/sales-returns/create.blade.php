@extends('layouts.app')
@section('title', 'New Sales Return')
@section('content')
@include('returns._form', [
    'title' => 'New Sales Return', 'prefix' => 'sales-returns', 'invoiceDoc' => $sale, 'action' => route('sales-returns.store'), 'invoiceField' => 'sale_id', 'detailField' => 'sale_detail_id',
    'party' => $sale?->customer, 'invoiceDate' => $sale?->sale_date, 'withCondition' => true, 'invoiceExample' => 'SAL-000001', 'refundLabel' => 'Refund to customer',
])
@endsection
@push('scripts')<script src="{{ asset('assets/js/returns.js') }}"></script>@endpush
