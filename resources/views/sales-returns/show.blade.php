@extends('layouts.app')
@section('title', $return->reference_no)
@section('content')
@include('returns._show', ['title' => 'Sales Return', 'prefix' => 'sales-returns', 'menuKey' => 'sales-returns', 'party' => $return->customer, 'partyLabel' => 'Customer', 'invoice' => $return->sale, 'invoiceRoute' => 'sales.show', 'withCondition' => true])
@endsection
