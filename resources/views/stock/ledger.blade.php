@extends('layouts.app')
@section('title', 'Stock Ledger')
@section('content')
<x-page-header title="Stock Ledger" sub="Every stock movement, newest first."><button class="btn btn-light" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button></x-page-header>
<x-filter search="Search by reference number">
    <x-filter-select name="product_id" label="Product" :options="$products->pluck('name', 'unique_id')" />
    <x-filter-select name="type" label="Type" :options="$types" />
    <x-filter-dates />
</x-filter>
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Date</th><th>Product</th><th>Type</th><th>Reference</th><th class="text-end">In</th><th class="text-end">Out</th><th class="text-end">Unit cost</th><th>Remarks</th><th>By</th></tr></thead>
            <tbody>
            @forelse ($transactions as $row)
                <tr>
                    <td class="text-nowrap">{{ $row->transaction_date->format('d M Y') }}</td>
                    <td>{{ $row->product?->name }}</td>
                    <td>{{ $row->type_label }}</td>
                    <td class="text-nowrap">{{ $row->reference_no }}</td>
                    <td class="num in">{{ (float) $row->quantity_in ? qty($row->quantity_in) : '' }}</td>
                    <td class="num out">{{ (float) $row->quantity_out ? qty($row->quantity_out) : '' }}</td>
                    <td class="num">{{ number_format($row->unit_cost, 2) }}</td>
                    <td>{{ $row->remarks }}</td>
                    <td>{{ $row->creator?->name }}</td>
                </tr>
            @empty
                <x-empty-row :cols="9" />
            @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :items="$transactions" />
</div>
@endsection
