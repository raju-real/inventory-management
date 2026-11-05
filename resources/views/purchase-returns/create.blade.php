@extends('layouts.app')
@section('title', 'New Purchase Return')
@section('content')
@include('returns._form', [
    'title' => 'New Purchase Return', 'prefix' => 'purchase-returns', 'invoiceDoc' => $purchase, 'action' => route('purchase-returns.store'), 'invoiceField' => 'purchase_id', 'detailField' => 'purchase_detail_id',
    'party' => $purchase?->supplier, 'invoiceDate' => $purchase?->purchase_date, 'withCondition' => false, 'invoiceExample' => 'PUR-000001', 'refundLabel' => 'Refund / adjustment',
])
@endsection
@push('scripts')<script src="{{ asset('assets/js/returns.js') }}"></script>@endpush
