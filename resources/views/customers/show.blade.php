@extends('layouts.app')
@section('title', $customer->name)
@section('content')
<x-page-header :title="$customer->name" :sub="$customer->mobile">
    <a class="btn btn-light" href="{{ route('customers.index') }}">Back</a>
    @access('report-customer-ledger')<a class="btn btn-outline-primary" href="{{ route('reports.customer-ledger', ['party_id' => $customer->unique_id]) }}"><i class="bi bi-journal-check me-1"></i>Ledger</a>@endaccess
    @access('customer-payments', 'create')<a class="btn btn-outline-primary btn-submit" href="{{ route('customer-payments.create', ['customer_id' => $customer->unique_id]) }}"><i class="bi bi-cash-coin me-1"></i>Receive Payment</a>@endaccess
    @access('customers', 'edit')<a class="btn btn-primary" href="{{ route('customers.edit', $customer) }}"><i class="bi bi-pencil me-1"></i>Edit</a>@endaccess
</x-page-header>
<div class="row g-3 mb-3">
    <div class="col-md-5">
        <div class="stat-card">
            @if ($customer->image_url)<img class="thumb" style="width:64px;height:64px" src="{{ $customer->image_url }}" alt="">@else<span class="thumb" style="width:64px;height:64px"><i class="bi bi-person fs-3"></i></span>@endif
            <div class="small">
                <div>{{ $customer->email ?: 'No email' }}</div>
                <div>{{ $customer->address ?: 'No address' }}</div>
                <x-status :active="$customer->status" />
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6"><div class="stat-card"><div><div class="stat-label">Opening balance</div><div class="stat-value">{{ number_format($customer->opening_balance, 2) }}</div></div></div></div>
    <div class="col-md-4 col-6"><div class="stat-card"><div><div class="stat-label">Outstanding balance</div><div class="stat-value {{ $customer->balance > 0 ? 'out' : '' }}">{{ money($customer->balance) }}</div></div></div></div>
</div>
<div class="card mb-3">
    <div class="card-header">Sales history</div>
    <div class="table-responsive"><table class="table">
        <thead><tr><th>Invoice</th><th>Date</th><th class="text-end">Quantity</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Due</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($sales as $sale)
            <tr>
                <td>@access('sales')<a href="{{ route('sales.show', $sale) }}">{{ $sale->reference_no }}</a>@else{{ $sale->reference_no }}@endaccess</td>
                <td>{{ $sale->sale_date->format('d M Y') }}</td><td class="num">{{ qty($sale->total_quantity) }}</td>
                <td class="num">{{ number_format($sale->grand_total, 2) }}</td><td class="num">{{ number_format($sale->paid_amount, 2) }}</td><td class="num">{{ number_format($sale->due_amount, 2) }}</td>
                <td><x-doc-status :status="$sale->status" /></td>
            </tr>
        @empty <x-empty-row :cols="7" text="No sales yet." /> @endforelse
        </tbody>
    </table></div>
    <x-pagination :items="$sales" />
</div>
@unless ($customer->is_walk_in)
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-stars me-1"></i>Loyalty points</span>
            <span class="badge tone-teal fs-6">{{ number_format($customer->points) }} points · {{ money($customer->points * $pointValue) }}</span>
        </div>
        <div class="table-responsive"><table class="table">
            <thead><tr><th>Date</th><th>What happened</th><th>Invoice</th><th>Note</th><th class="text-end">Points</th></tr></thead>
            <tbody>
            @forelse ($pointHistory as $entry)
                <tr>
                    <td>{{ $entry->created_at->format('d M Y h:i A') }}</td>
                    <td>{{ $entry->type_label }}</td>
                    <td>{{ $entry->sale?->reference_no }}</td>
                    <td>{{ $entry->remarks }}{{ (float) $entry->amount > 0 ? ' (' . money($entry->amount) . ')' : '' }}</td>
                    <td class="num {{ $entry->points > 0 ? 'in' : 'out' }}">{{ $entry->points > 0 ? '+' : '' }}{{ number_format($entry->points) }}</td>
                </tr>
            @empty <x-empty-row :cols="5" text="No points yet." /> @endforelse
            </tbody>
        </table></div>
        <x-pagination :items="$pointHistory" />
    </div>
@endunless
<div class="card">
    <div class="card-header">Return history</div>
    <div class="table-responsive"><table class="table">
        <thead><tr><th>Return</th><th>Invoice</th><th>Date</th><th class="text-end">Quantity</th><th class="text-end">Value</th><th class="text-end">Refund</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($returns as $return)
            <tr>
                <td>@access('sales-returns')<a href="{{ route('sales-returns.show', $return) }}">{{ $return->reference_no }}</a>@else{{ $return->reference_no }}@endaccess</td>
                <td>{{ $return->sale?->reference_no }}</td><td>{{ $return->return_date->format('d M Y') }}</td><td class="num">{{ qty($return->total_quantity) }}</td>
                <td class="num">{{ number_format($return->total_amount, 2) }}</td><td class="num">{{ number_format($return->refund_amount, 2) }}</td><td><x-doc-status :status="$return->status" /></td>
            </tr>
        @empty <x-empty-row :cols="7" text="No returns yet." /> @endforelse
        </tbody>
    </table></div>
    <x-pagination :items="$returns" />
</div>
@endsection
