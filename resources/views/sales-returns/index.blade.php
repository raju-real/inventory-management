@extends('layouts.app')
@section('title', 'Sales Returns')
@section('content')
@include('returns._index', ['title' => 'Sales Returns', 'prefix' => 'sales-returns', 'menuKey' => 'sales-returns', 'partyLabel' => 'Customer', 'partyField' => 'customer', 'invoiceField' => 'sale', 'newLabel' => 'New Sales Return'])
@endsection
