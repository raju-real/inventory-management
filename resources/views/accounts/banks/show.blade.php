@extends('layouts.app')
@section('title', $bank->bank_name)
@section('content')
<x-page-header :title="$bank->bank_name" :sub="trim($bank->branch . ' · ' . $bank->account_number, ' ·')">
    <a class="btn btn-light" href="{{ route('bank-accounts.index') }}">Back</a>
    <button class="btn btn-light" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
    @access('expenses', 'create')<a class="btn btn-outline-primary btn-submit" href="{{ route('expenses.create') }}"><i class="bi bi-dash-circle me-1"></i>Add Expense</a>@endaccess
    @access('incomes', 'create')<a class="btn btn-outline-primary btn-submit" href="{{ route('incomes.create') }}"><i class="bi bi-plus-circle me-1"></i>Add Income</a>@endaccess
    @access('bank-accounts', 'edit')<a class="btn btn-primary" href="{{ route('bank-accounts.edit', $bank) }}"><i class="bi bi-pencil me-1"></i>Edit</a>@endaccess
</x-page-header>
<div class="row g-3 mb-3">
    @foreach ([['Current balance', $bank->current_balance, 'bi-bank', 'tone-teal'], ['Opening for this period', $statement['opening'], 'bi-box-arrow-in-right', 'tone-blue'], ['Money in', $statement['in'], 'bi-arrow-down-left', 'tone-green'], ['Money out', $statement['out'], 'bi-arrow-up-right', 'tone-red']] as [$label, $value, $icon, $tone])
        <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-icon {{ $tone }}"><i class="bi {{ $icon }}"></i></div><div><div class="stat-label">{{ $label }}</div><div class="stat-value">{{ money($value) }}</div></div></div></div>
    @endforeach
</div>
<x-filter><x-filter-dates /></x-filter>
<div class="card">
    <div class="card-header">Statement</div>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Date</th><th>Type</th><th>Category</th><th>Reference</th><th>Notes</th><th class="text-end">In</th><th class="text-end">Out</th><th class="text-end">Balance</th></tr></thead>
            <tbody>
            @if ($statement['rows']->onFirstPage())
                <tr class="table-light"><td colspan="7" class="fw-medium">Opening balance</td><td class="num fw-medium">{{ number_format($statement['opening'], 2) }}</td></tr>
            @endif
            @forelse ($statement['rows'] as $row)
                <tr>
                    <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d M Y') }}</td>
                    <td><span class="badge {{ $row['type'] === 'Income' ? 'tone-green' : 'tone-red' }}">{{ $row['type'] }}</span></td>
                    <td>{{ $row['category'] }}</td>
                    <td>{{ $row['reference'] }}</td>
                    <td>{{ $row['notes'] }}</td>
                    <td class="num in">{{ $row['in'] ? number_format($row['in'], 2) : '' }}</td>
                    <td class="num out">{{ $row['out'] ? number_format($row['out'], 2) : '' }}</td>
                    <td class="num fw-medium">{{ number_format($row['balance'], 2) }}</td>
                </tr>
            @empty
                <x-empty-row :cols="8" text="No transactions in this period." />
            @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :items="$statement['rows']" />
</div>
@endsection
