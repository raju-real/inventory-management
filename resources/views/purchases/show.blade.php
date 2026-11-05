@extends('layouts.app')
@section('title', $purchase->reference_no)
@section('content')
@include('invoices._show', [
    'invoice' => $purchase, 'party' => $purchase->supplier, 'date' => $purchase->purchase_date, 'prefix' => 'purchases', 'menuKey' => 'purchases', 'title' => 'Purchase', 'partyLabel' => 'Supplier',
    'partyRoute' => 'suppliers.show', 'paymentRoute' => 'supplier-payments.create', 'paymentMenu' => 'supplier-payments', 'paymentParam' => 'purchase_id',
    'returnRoute' => 'purchase-returns.create', 'returnMenu' => 'purchase-returns',
])
@endsection
