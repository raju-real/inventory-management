@extends('layouts.app')
@section('title', $title)
@section('content')
<x-page-header :title="$title" :sub="'Printed ' . now()->format('d M Y H:i')"><button class="btn btn-light" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button></x-page-header>
<x-filter search="Search by name, code or barcode" scan>
    <x-filter-select name="category_id" label="Category" :options="$categories->pluck('name', 'unique_id')" />
</x-filter>
@include('stock._table', ['products' => $products])
@endsection
