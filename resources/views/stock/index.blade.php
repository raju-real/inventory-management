@extends('layouts.app')
@section('title', 'Stock Overview')
@section('content')
<x-page-header title="Stock Overview" sub="Available = opening + purchases + good customer returns - sales - supplier returns - damage + adjustments">
    @access('stock-adjustments', 'create')<a class="btn btn-primary btn-submit" href="{{ route('stock-adjustments.create') }}"><i class="bi bi-sliders me-1"></i>Adjust Stock</a>@endaccess
</x-page-header>
<x-filter search="Search by name, code or barcode" scan>
    <x-filter-select name="category_id" label="Category" :options="$categories->pluck('name', 'unique_id')" />
    <x-filter-select name="low_stock" label="Stock" :options="[1 => 'Low stock only']" />
</x-filter>
@include('stock._table', ['products' => $products])
@endsection
