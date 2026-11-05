@extends('layouts.app')
@section('title', $sale->reference_no)
@section('content')
@include('invoices._show', [
    'invoice' => $sale, 'party' => $sale->customer, 'date' => $sale->sale_date, 'prefix' => 'sales', 'menuKey' => 'sales', 'title' => 'Sale', 'partyLabel' => 'Customer',
    'partyRoute' => 'customers.show', 'paymentRoute' => 'customer-payments.create', 'paymentMenu' => 'customer-payments', 'paymentParam' => 'sale_id',
    'returnRoute' => 'sales-returns.create', 'returnMenu' => 'sales-returns',
])
@endsection
