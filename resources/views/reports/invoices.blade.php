@extends('layouts.app')
@section('title', $title)
@section('content')
<x-page-header :title="$title"><button class="btn btn-light" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button></x-page-header>
<x-filter>
    <x-filter-select name="group" label="Show" placeholder="Invoice wise" :options="['date' => 'Date wise', 'party' => $partyLabel . ' wise']" />
    <x-filter-select name="party_id" :label="$partyLabel" :options="$parties->pluck('name', 'unique_id')" />
    <x-filter-dates />
</x-filter>
<div class="row g-3 mb-3">
    @php
        $cell = fn ($value, $kind) => $kind === 'count' ? number_format((float) $value) : number_format((float) $value, 2);
        $cards = ['Invoices' => number_format($totals->invoices), 'Quantity' => qty($totals->quantity)];
        foreach ($columns as $column => [$label, $kind]) {
            $cards[$label] = $kind === 'count' ? number_format((float) $totals->{$column}) : money($totals->{$column});
        }
        $cards += ['Total' => money($totals->total), 'Paid' => money($totals->paid), 'Due' => money($totals->due)];
    @endphp
    @foreach ($cards as $label => $value)
        <div class="col-6 col-md-4 col-xl-3"><div class="stat-card"><div><div class="stat-label">{{ $label }}</div><div class="stat-value">{{ $value }}</div></div></div></div>
    @endforeach
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    @if ($group === 'date') <th>Date</th><th class="text-end">Invoices</th>
                    @elseif ($group === 'party') <th>{{ $partyLabel }}</th><th class="text-end">Invoices</th>
                    @else <th>Invoice</th><th>Date</th><th>{{ $partyLabel }}</th> @endif
                    <th class="text-end">Quantity</th>@foreach ($columns as [$label]) <th class="text-end">{{ $label }}</th> @endforeach<th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Due</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    @if ($group === 'date') <td>{{ \Illuminate\Support\Carbon::parse($row->label)->format('d M Y') }}</td><td class="num">{{ $row->invoices }}</td>
                    @elseif ($group === 'party') <td>{{ $row->label }}</td><td class="num">{{ $row->invoices }}</td>
                    @else <td>{{ $row->reference_no }}</td><td>{{ \Illuminate\Support\Carbon::parse($row->invoice_date)->format('d M Y') }}</td><td>{{ $row->party_name }}</td> @endif
                    <td class="num">{{ qty($row->quantity) }}</td>@foreach ($columns as $column => [$label, $kind]) <td class="num">{{ $cell($row->{$column}, $kind) }}</td> @endforeach<td class="num">{{ number_format($row->total, 2) }}</td><td class="num">{{ number_format($row->paid, 2) }}</td><td class="num">{{ number_format($row->due, 2) }}</td>
                </tr>
            @empty
                <x-empty-row :cols="7 + count($columns)" />
            @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :items="$rows" />
</div>
@endsection
