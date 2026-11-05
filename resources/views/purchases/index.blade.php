@extends('layouts.app')
@section('title', 'Purchases')
@section('content')
@include('invoices._index', [
    'title' => 'Purchases', 'rows' => $purchases, 'prefix' => 'purchases', 'menuKey' => 'purchases', 'partyLabel' => 'Supplier', 'partyField' => 'supplier',
    'dateField' => 'purchase_date', 'parties' => $suppliers, 'searchHint' => 'Search by invoice number or supplier', 'newLabel' => 'New Purchase',
])
@endsection
