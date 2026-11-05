@extends('layouts.app')
@section('title', 'Sales')
@section('content')
@include('invoices._index', [
    'title' => 'Sales', 'rows' => $sales, 'prefix' => 'sales', 'menuKey' => 'sales', 'partyLabel' => 'Customer', 'partyField' => 'customer',
    'dateField' => 'sale_date', 'parties' => $customers, 'searchHint' => 'Search by invoice number, customer or mobile', 'newLabel' => 'New Sale',
])
@endsection
