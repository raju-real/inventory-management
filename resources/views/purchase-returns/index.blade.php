@extends('layouts.app')
@section('title', 'Purchase Returns')
@section('content')
@include('returns._index', ['title' => 'Purchase Returns', 'prefix' => 'purchase-returns', 'menuKey' => 'purchase-returns', 'partyLabel' => 'Supplier', 'partyField' => 'supplier', 'invoiceField' => 'purchase', 'newLabel' => 'New Purchase Return'])
@endsection
