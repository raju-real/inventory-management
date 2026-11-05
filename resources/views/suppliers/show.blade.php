@extends('layouts.app')
@section('title', $supplier->name)
@section('content')
<x-page-header :title="$supplier->name" :sub="$supplier->contact_person">
    <a class="btn btn-light" href="{{ route('suppliers.index') }}">Back</a>
    @access('report-supplier-ledger')<a class="btn btn-outline-primary" href="{{ route('reports.supplier-ledger', ['party_id' => $supplier->unique_id]) }}"><i class="bi bi-journal-arrow-down me-1"></i>Ledger</a>@endaccess
    @access('supplier-payments', 'create')<a class="btn btn-outline-primary btn-submit" href="{{ route('supplier-payments.create', ['supplier_id' => $supplier->unique_id]) }}"><i class="bi bi-wallet2 me-1"></i>Make Payment</a>@endaccess
    @access('suppliers', 'edit')<a class="btn btn-primary" href="{{ route('suppliers.edit', $supplier) }}"><i class="bi bi-pencil me-1"></i>Edit</a>@endaccess
</x-page-header>
<div class="row g-3 mb-3">
    <div class="col-md-5">
        <div class="stat-card">
            @if ($supplier->image_url)<img class="thumb" style="width:64px;height:64px" src="{{ $supplier->image_url }}" alt="">@else<span class="thumb" style="width:64px;height:64px"><i class="bi bi-truck fs-3"></i></span>@endif
            <div class="small">
                <div>{{ $supplier->mobile ?: 'No mobile' }}</div>
                <div>{{ $supplier->email ?: 'No email' }}</div>
                <div>{{ $supplier->address ?: 'No address' }}</div>
                <x-status :active="$supplier->status" />
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6"><div class="stat-card"><div><div class="stat-label">Opening balance</div><div class="stat-value">{{ number_format($supplier->opening_balance, 2) }}</div></div></div></div>
    <div class="col-md-4 col-6"><div class="stat-card"><div><div class="stat-label">Outstanding payable</div><div class="stat-value {{ $supplier->balance > 0 ? 'out' : '' }}">{{ money($supplier->balance) }}</div></div></div></div>
</div>
<div class="card mb-3">
    <div class="card-header">Purchase history</div>
    <div class="table-responsive"><table class="table">
        <thead><tr><th>Invoice</th><th>Date</th><th class="text-end">Quantity</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Due</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($purchases as $purchase)
            <tr>
                <td>@access('purchases')<a href="{{ route('purchases.show', $purchase) }}">{{ $purchase->reference_no }}</a>@else{{ $purchase->reference_no }}@endaccess</td>
                <td>{{ $purchase->purchase_date->format('d M Y') }}</td><td class="num">{{ qty($purchase->total_quantity) }}</td>
                <td class="num">{{ number_format($purchase->grand_total, 2) }}</td><td class="num">{{ number_format($purchase->paid_amount, 2) }}</td><td class="num">{{ number_format($purchase->due_amount, 2) }}</td>
                <td><x-doc-status :status="$purchase->status" /></td>
            </tr>
        @empty <x-empty-row :cols="7" text="No purchases yet." /> @endforelse
        </tbody>
    </table></div>
    <x-pagination :items="$purchases" />
</div>
<div class="card">
    <div class="card-header">Return history</div>
    <div class="table-responsive"><table class="table">
        <thead><tr><th>Return</th><th>Invoice</th><th>Date</th><th class="text-end">Quantity</th><th class="text-end">Value</th><th class="text-end">Refund</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($returns as $return)
            <tr>
                <td>@access('purchase-returns')<a href="{{ route('purchase-returns.show', $return) }}">{{ $return->reference_no }}</a>@else{{ $return->reference_no }}@endaccess</td>
                <td>{{ $return->purchase?->reference_no }}</td><td>{{ $return->return_date->format('d M Y') }}</td><td class="num">{{ qty($return->total_quantity) }}</td>
                <td class="num">{{ number_format($return->total_amount, 2) }}</td><td class="num">{{ number_format($return->refund_amount, 2) }}</td><td><x-doc-status :status="$return->status" /></td>
            </tr>
        @empty <x-empty-row :cols="7" text="No returns yet." /> @endforelse
        </tbody>
    </table></div>
    <x-pagination :items="$returns" />
</div>
@endsection
