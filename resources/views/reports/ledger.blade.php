@extends('layouts.app')
@section('title', $title)
@section('content')
<x-page-header :title="$title" :sub="$party?->name"><button class="btn btn-light" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button></x-page-header>
<x-filter>
    <div class="col-md-3">
        <label class="form-label small" for="party_id">{{ $partyLabel }}</label>
        <select class="form-select" id="party_id" name="party_id" required>
            <option value="">Select {{ strtolower($partyLabel) }}</option>
            @foreach ($parties as $option)
                <option value="{{ $option->unique_id }}" @selected((string) request('party_id') === (string) $option->unique_id)>{{ $option->name }}{{ $option->mobile ? ' (' . $option->mobile . ')' : '' }}</option>
            @endforeach
        </select>
    </div>
    <x-filter-dates />
</x-filter>
@if ($ledger)
    <div class="row g-3 mb-3">
        @foreach (['Opening balance' => $ledger['opening'], 'Total debit' => $ledger['debit'], 'Total credit' => $ledger['credit'], 'Closing balance' => $ledger['closing']] as $label => $value)
            <div class="col-6 col-md-3"><div class="stat-card"><div><div class="stat-label">{{ $label }}</div><div class="stat-value">{{ money($value) }}</div></div></div></div>
        @endforeach
    </div>
    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Date</th><th>Transaction</th><th>Reference</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th class="text-end">Balance</th></tr></thead>
                <tbody>
                @if ($ledger['rows']->onFirstPage())
                    <tr class="table-light"><td colspan="5" class="fw-medium">Opening balance</td><td class="num fw-medium">{{ number_format($ledger['opening'], 2) }}</td></tr>
                @endif
                @forelse ($ledger['rows'] as $row)
                    <tr>
                        <td>{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d M Y') }}</td><td>{{ $row['type'] }}</td><td>{{ $row['reference'] }}</td>
                        <td class="num">{{ $row['debit'] ? number_format($row['debit'], 2) : '' }}</td><td class="num">{{ $row['credit'] ? number_format($row['credit'], 2) : '' }}</td><td class="num">{{ number_format($row['balance'], 2) }}</td>
                    </tr>
                @empty
                    <x-empty-row :cols="6" text="No transactions in this period." />
                @endforelse
                </tbody>
            </table>
        </div>
        <x-pagination :items="$ledger['rows']" />
        <div class="card-body border-top small text-muted py-2">{{ $balanceHint }}</div>
    </div>
@else
    <div class="card card-body text-center text-muted py-5">Select a {{ strtolower($partyLabel) }} to view the ledger.</div>
@endif
@endsection
