@extends('layouts.app')
@section('title', $return->reference_no)
@section('content')
@include('returns._show', ['title' => 'Purchase Return', 'prefix' => 'purchase-returns', 'menuKey' => 'purchase-returns', 'party' => $return->supplier, 'partyLabel' => 'Supplier', 'invoice' => $return->purchase, 'invoiceRoute' => 'purchases.show', 'withCondition' => false])
@endsection
