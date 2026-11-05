@extends('layouts.app')
@section('title', 'Bank Accounts')
@section('content')
<x-page-header title="Bank Accounts" sub="Balances update automatically from incomes and expenses.">
    @access('bank-accounts', 'create')<a class="btn btn-primary btn-submit" href="{{ route('bank-accounts.create') }}"><i class="bi bi-plus-lg me-1"></i>Add Bank Account</a>@endaccess
</x-page-header>
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-4"><div class="stat-card"><div class="stat-icon tone-teal"><i class="bi bi-bank"></i></div><div><div class="stat-label">Total balance</div><div class="stat-value">{{ money($summary['balance']) }}</div><div class="stat-sub">Across active accounts</div></div></div></div>
    <div class="col-sm-6 col-xl-4"><div class="stat-card"><div class="stat-icon tone-blue"><i class="bi bi-credit-card-2-front"></i></div><div><div class="stat-label">Active accounts</div><div class="stat-value">{{ $summary['count'] }}</div><div class="stat-sub">Ready to use</div></div></div></div>
</div>
<x-filter search="Search by bank, branch or account number">
    <x-filter-select name="status" label="Status" :options="[1 => 'Active', 0 => 'Inactive']" />
</x-filter>
<div class="row g-3">
    @forelse ($banks as $bank)
        <div class="col-md-6 col-xl-4">
            <div class="bank-card {{ $bank->status ? '' : 'inactive' }}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="min-w-0">
                        <div class="bank-name text-truncate">{{ $bank->bank_name }}</div>
                        <div class="bank-branch text-truncate">{{ $bank->branch ?: 'No branch set' }}</div>
                    </div>
                    <i class="bi bi-bank bank-mark"></i>
                </div>
                <div class="bank-number">{{ $bank->account_number }}</div>
                <div class="d-flex justify-content-between align-items-end">
                    <div>
                        <div class="bank-label">Current balance</div>
                        <div class="bank-balance">{{ money($bank->current_balance) }}</div>
                    </div>
                    <div class="bank-actions">
                        @unless ($bank->status)<span class="badge bg-light text-dark me-1">Inactive</span>@endunless
                        <a class="btn btn-sm btn-light" title="Statement" href="{{ route('bank-accounts.show', $bank) }}"><i class="bi bi-journal-text"></i></a>
                        @access('bank-accounts', 'edit')<a class="btn btn-sm btn-light" title="Edit" href="{{ route('bank-accounts.edit', $bank) }}"><i class="bi bi-pencil"></i></a>@endaccess
                        @access('bank-accounts', 'delete')<button class="btn btn-sm btn-light text-danger" title="Delete" data-delete-url="{{ route('bank-accounts.destroy', $bank) }}" data-name="{{ $bank->bank_name }}"><i class="bi bi-trash"></i></button>@endaccess
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="card card-body text-center text-muted py-5"><i class="bi bi-bank d-block fs-2 mb-1"></i>No bank accounts yet.</div></div>
    @endforelse
</div>
@if ($banks->hasPages())<div class="mt-3">{{ $banks->onEachSide(1)->links() }}</div>@endif
@endsection
