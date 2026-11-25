@extends('layouts.app')
@section('title', 'Due List')
@section('content')
@php
    $other = $type === 'customers' ? 'suppliers' : 'customers';
    $query = request()->except('page');
    $pdfQuery = array_merge($query, ['type' => $type, 'view' => $view]);
    $canOpenParty = auth()->user()->canAccess($t['party']);
    $canOpenInvoice = auth()->user()->canAccess($t['invoice']);
@endphp
<x-page-header title="Due List" :sub="$type === 'customers' ? 'Money customers still owe you.' : 'Money you still owe suppliers.'">
    <a class="btn btn-outline-primary" href="{{ route('reports.due-list.pdf', $pdfQuery) }}" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf me-1"></i>{{ $view === 'invoice' ? 'Statement PDF' : 'PDF' }}</a>
    <a class="btn btn-light" href="{{ route('reports.due-list.pdf', $pdfQuery + ['download' => 1]) }}" title="Download PDF" aria-label="Download PDF"><i class="bi bi-download"></i></a>
</x-page-header>

<div class="d-flex flex-wrap gap-2 justify-content-between mb-3 no-print">
    <ul class="nav nav-pills">
        <li class="nav-item"><a class="nav-link {{ $type === 'customers' ? 'active' : '' }}" href="{{ route('reports.due-list', ['type' => 'customers', 'view' => $view]) }}"><i class="bi bi-person me-1"></i>Customer dues</a></li>
        <li class="nav-item"><a class="nav-link {{ $type === 'suppliers' ? 'active' : '' }}" href="{{ route('reports.due-list', ['type' => 'suppliers', 'view' => $view]) }}"><i class="bi bi-truck me-1"></i>Supplier dues</a></li>
    </ul>
    <div class="btn-group" role="group" aria-label="Show">
        <a class="btn {{ $view === 'party' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('reports.due-list', array_merge($query, ['type' => $type, 'view' => 'party'])) }}">{{ $t['label'] }} wise</a>
        <a class="btn {{ $view === 'invoice' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('reports.due-list', array_merge($query, ['type' => $type, 'view' => 'invoice'])) }}">Invoice wise</a>
    </div>
</div>

<x-filter :action="route('reports.due-list', ['type' => $type, 'view' => $view])">
    <input type="hidden" name="type" value="{{ $type }}">
    <input type="hidden" name="view" value="{{ $view }}">
    <x-filter-remote name="party_id" :label="$t['label']" :type="$type" />
    @if ($view === 'invoice')
        <x-filter-dates />
    @endif
</x-filter>

<div class="row g-3 mb-3">
    @php
        $cards = $view === 'invoice'
            ? ['Due invoices' => number_format($totals->invoices), $t['plural'] => number_format($totals->parties), 'Invoice total' => money($totals->grand_total), $t['paid'] => money($totals->paid), 'Returned' => money($totals->returned), 'Total due' => money($totals->due)]
            : [$t['plural'] . ' with due' => number_format($totals->parties), 'Due invoices' => number_format($totals->due_invoices), 'Due on invoices' => money($totals->invoice_due), 'Opening / unlinked' => money($totals->other), 'Total due' => money($totals->balance)];
    @endphp
    @foreach ($cards as $label => $value)
        <div class="col-6 col-md-4 col-xl"><div class="stat-card {{ $label === 'Total due' ? 'stat-due' : '' }}"><div><div class="stat-label">{{ $label }}</div><div class="stat-value">{{ $value }}</div></div></div></div>
    @endforeach
</div>

<div class="card">
    <div class="table-responsive">
        @if ($view === 'invoice')
            <table class="table">
                <thead><tr><th>Invoice</th><th>Date</th><th class="text-end">Days</th><th>{{ $t['label'] }}</th><th class="text-end">Invoice total</th><th class="text-end">{{ $t['paid'] }}</th><th class="text-end">Returned</th><th class="text-end">Due</th></tr></thead>
                <tbody>
                @forelse ($rows as $row)
                    @php $date = \Illuminate\Support\Carbon::parse($row->invoice_date); $days = (int) $date->startOfDay()->diffInDays(now()->startOfDay()); @endphp
                    <tr>
                        <td>@if ($canOpenInvoice)<a class="fw-medium text-decoration-none" href="{{ route($t['route'], $row->unique_id) }}">{{ $row->reference_no }}</a>@else{{ $row->reference_no }}@endif</td>
                        <td class="text-nowrap">{{ $date->format('d M Y') }}</td>
                        <td class="num"><span class="badge {{ $days > 60 ? 'text-bg-danger' : ($days > 30 ? 'text-bg-warning' : 'text-bg-light border') }}">{{ $days }}</span></td>
                        <td>
                            <div class="fw-medium">{{ $row->party_name }}</div>
                            @if ($row->party_mobile)<div class="small text-muted">{{ $row->party_mobile }}</div>@endif
                        </td>
                        <td class="num">{{ number_format($row->grand_total, 2) }}</td>
                        <td class="num">{{ number_format($row->paid, 2) }}</td>
                        <td class="num">{{ number_format($row->returned, 2) }}</td>
                        <td class="num fw-semibold text-danger">{{ number_format($row->due, 2) }}</td>
                    </tr>
                @empty
                    <x-empty-row :cols="8" text="No dues. Everything is paid." />
                @endforelse
                </tbody>
                @if ($rows->total() > 0)
                    <tfoot><tr class="fw-semibold"><td colspan="4">Total ({{ number_format($totals->invoices) }} invoices)</td><td class="num">{{ number_format($totals->grand_total, 2) }}</td><td class="num">{{ number_format($totals->paid, 2) }}</td><td class="num">{{ number_format($totals->returned, 2) }}</td><td class="num text-danger">{{ number_format($totals->due, 2) }}</td></tr></tfoot>
                @endif
            </table>
        @else
            <table class="table">
                <thead><tr><th>{{ $t['label'] }}</th><th class="text-end">Due invoices</th><th class="text-end">Invoice total</th><th class="text-end">{{ $t['paid'] }}</th><th class="text-end">Returned</th><th class="text-end">Due on invoices</th><th class="text-end" title="Opening balance, and payments not linked to an invoice">Opening / unlinked</th><th class="text-end">Total due</th><th></th></tr></thead>
                <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>
                            @if ($canOpenParty)<a class="fw-medium text-decoration-none" href="{{ route($t['party'] . '.show', $row->unique_id) }}">{{ $row->name }}</a>@else<span class="fw-medium">{{ $row->name }}</span>@endif
                            @if ($row->mobile)<div class="small text-muted">{{ $row->mobile }}</div>@endif
                        </td>
                        <td class="num">{{ number_format($row->due_invoices) }}</td>
                        <td class="num">{{ number_format($row->invoice_total, 2) }}</td>
                        <td class="num">{{ number_format($row->invoice_paid, 2) }}</td>
                        <td class="num">{{ number_format($row->invoice_returned, 2) }}</td>
                        <td class="num">{{ number_format($row->invoice_due, 2) }}</td>
                        <td class="num">{{ abs($row->other) < 0.005 ? '-' : number_format($row->other, 2) }}</td>
                        <td class="num fw-semibold {{ $row->balance > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($row->balance, 2) }}</td>
                        <td class="table-actions">
                            <a class="btn btn-sm btn-light" href="{{ route('reports.due-list', ['type' => $type, 'view' => 'invoice', 'party_id' => $row->unique_id]) }}" title="Due invoices" aria-label="Due invoices"><i class="bi bi-list-ul"></i></a>
                            <a class="btn btn-sm btn-light" href="{{ route('reports.due-list.pdf', ['type' => $type, 'view' => 'invoice', 'party_id' => $row->unique_id]) }}" target="_blank" rel="noopener" title="Due statement PDF" aria-label="Due statement PDF"><i class="bi bi-file-earmark-pdf"></i></a>
                        </td>
                    </tr>
                @empty
                    <x-empty-row :cols="9" text="No dues. Everything is paid." />
                @endforelse
                </tbody>
                @if ($rows->total() > 0)
                    <tfoot><tr class="fw-semibold"><td>Total ({{ number_format($totals->parties) }})</td><td class="num">{{ number_format($totals->due_invoices) }}</td><td class="num">{{ number_format($totals->invoice_total, 2) }}</td><td class="num">{{ number_format($totals->invoice_paid, 2) }}</td><td class="num">{{ number_format($totals->invoice_returned, 2) }}</td><td class="num">{{ number_format($totals->invoice_due, 2) }}</td><td class="num">{{ number_format($totals->other, 2) }}</td><td class="num text-danger">{{ number_format($totals->balance, 2) }}</td><td></td></tr></tfoot>
                @endif
            </table>
        @endif
    </div>
    <x-pagination :items="$rows" />
</div>
@endsection
