@extends('layouts.app')
@section('title', 'Damage & Return Report')
@section('content')
<x-page-header title="Damage &amp; Return Report"><button class="btn btn-light" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button></x-page-header>
<ul class="nav nav-pills mb-3 no-print">
    @foreach (['damage' => 'Damage', 'sales_return' => 'Sales Returns', 'purchase_return' => 'Purchase Returns'] as $key => $label)
        <li class="nav-item"><a class="nav-link {{ $type === $key ? 'active' : '' }}" @if ($type === $key) style="background:var(--brand)" @endif href="{{ route('reports.damage-returns', ['type' => $key]) }}">{{ $label }}</a></li>
    @endforeach
</ul>
<form method="GET" class="card card-body mb-3 no-print">
    <input type="hidden" name="type" value="{{ $type }}">
    <div class="row g-2 align-items-end">
        <x-filter-remote name="product_id" label="Product" type="products" />
        <x-filter-dates />
        <div class="col-auto ms-auto"><a class="btn btn-light" href="{{ route('reports.damage-returns', ['type' => $type]) }}">Reset</a> <button class="btn btn-primary btn-submit" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button></div>
    </div>
</form>
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3"><div class="stat-card"><div><div class="stat-label">Total quantity</div><div class="stat-value">{{ qty($totals->quantity) }}</div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div><div class="stat-label">Total value</div><div class="stat-value">{{ money($totals->amount) }}</div></div></div></div>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Reference</th><th>Date</th><th>Product</th><th>{{ $type === 'damage' ? 'Source' : ($type === 'sales_return' ? 'Customer' : 'Supplier') }}</th>@if ($type === 'sales_return')<th>Condition</th>@endif<th>Reason</th><th class="text-end">Quantity</th><th class="text-end">Value</th></tr></thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->reference_no }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($row->entry_date)->format('d M Y') }}</td>
                    <td>{{ $row->product_name }}</td>
                    <td>{{ $row->source }}</td>
                    @if ($type === 'sales_return')<td class="text-capitalize">{{ $row->item_condition }}</td>@endif
                    <td>{{ $row->detail }}</td>
                    <td class="num">{{ qty($row->quantity) }}</td>
                    <td class="num">{{ number_format($row->amount, 2) }}</td>
                </tr>
            @empty
                <x-empty-row :cols="8" />
            @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :items="$rows" />
</div>
@endsection
