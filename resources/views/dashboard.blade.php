@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<x-page-header title="Dashboard" :sub="now()->format('l, d F Y')" />

<div class="row g-3 mb-3">
    @foreach ([
        ['Total Products', number_format($stats['total_products']), 'Active products', 'bi-box', 'tone-teal'],
        ['Available Stock', qty($stats['available_stock']), 'Units across all products', 'bi-stack', 'tone-blue'],
        ["Today's Sales", money($stats['today_sales_amount']), qty($stats['today_sales_qty']) . ' units sold', 'bi-graph-up', 'tone-green'],
        ["Today's Purchases", money($stats['today_purchases_amount']), qty($stats['today_purchases_qty']) . ' units bought', 'bi-bag-plus', 'tone-violet'],
        ['Damaged Items', qty($stats['damaged_items']), 'Units recorded as damaged', 'bi-exclamation-triangle', 'tone-amber'],
        ['Low Stock Products', number_format($stats['low_stock']), 'At or below minimum', 'bi-bell', 'tone-red'],
    ] as [$label, $value, $sub, $icon, $tone])
        <div class="col-sm-6 col-xl-4">
            <div class="stat-card">
                <div class="stat-icon {{ $tone }}"><i class="bi {{ $icon }}"></i></div>
                <div class="min-w-0">
                    <div class="stat-label">{{ $label }}</div>
                    <div class="stat-value">{{ $value }}</div>
                    <div class="stat-sub">{{ $sub }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3 mb-3">
    @access('bank-accounts')
        <div class="col-sm-6 col-xl-4"><a class="stat-card text-reset text-decoration-none" href="{{ route('bank-accounts.index') }}"><div class="stat-icon tone-teal"><i class="bi bi-bank"></i></div><div><div class="stat-label">Bank Balance</div><div class="stat-value">{{ money($accounts['balance']) }}</div><div class="stat-sub">All active accounts</div></div></a></div>
    @endaccess
    @access('incomes')
        <div class="col-sm-6 col-xl-4"><a class="stat-card text-reset text-decoration-none" href="{{ route('incomes.index') }}"><div class="stat-icon tone-green"><i class="bi bi-arrow-down-left"></i></div><div><div class="stat-label">Income This Month</div><div class="stat-value">{{ money($accounts['income']) }}</div><div class="stat-sub">{{ now()->format('F Y') }}</div></div></a></div>
    @endaccess
    @access('expenses')
        <div class="col-sm-6 col-xl-4"><a class="stat-card text-reset text-decoration-none" href="{{ route('expenses.index') }}"><div class="stat-icon tone-red"><i class="bi bi-arrow-up-right"></i></div><div><div class="stat-label">Expense This Month</div><div class="stat-value">{{ money($accounts['expense']) }}</div><div class="stat-sub">{{ now()->format('F Y') }}</div></div></a></div>
    @endaccess
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header">Recent Transactions</div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Transaction</th><th>Reference</th><th>Product</th><th>Date</th><th class="text-end">Quantity</th></tr></thead>
                    <tbody>
                    @forelse ($stats['recent'] as $row)
                        <tr>
                            <td>{{ $row->type_label }}</td>
                            <td>{{ $row->reference_no }}</td>
                            <td>{{ $row->product?->name }}</td>
                            <td>{{ $row->transaction_date->format('d M Y') }}</td>
                            <td class="num">
                                @if ((float) $row->quantity_in > 0)<span class="in">+{{ qty($row->quantity_in) }}</span>
                                @elseif ((float) $row->quantity_out > 0)<span class="out">-{{ qty($row->quantity_out) }}</span>
                                @else <span class="text-muted">0</span>@endif
                            </td>
                        </tr>
                    @empty
                        <x-empty-row :cols="5" text="No stock movement yet." />
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">Low Stock</div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Product</th><th class="text-end">Stock</th><th class="text-end">Min</th></tr></thead>
                    <tbody>
                    @forelse ($stats['low_stock_products'] as $product)
                        <tr><td>{{ $product->name }}</td><td class="num out">{{ qty($product->current_stock) }}</td><td class="num">{{ qty($product->min_stock) }}</td></tr>
                    @empty
                        <x-empty-row :cols="3" text="All products are above their minimum." />
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
