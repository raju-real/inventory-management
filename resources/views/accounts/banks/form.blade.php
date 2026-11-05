@extends('layouts.app')
@section('title', $bank->exists ? 'Edit Bank Account' : 'Add Bank Account')
@section('content')
<x-page-header :title="$bank->exists ? 'Edit Bank Account' : 'Add Bank Account'"><a class="btn btn-light" href="{{ route('bank-accounts.index') }}">Back</a></x-page-header>
<div class="card" style="max-width:720px"><div class="card-body">
    <form method="POST" action="{{ $bank->exists ? route('bank-accounts.update', $bank) : route('bank-accounts.store') }}" class="needs-validation" novalidate autocomplete="off">
        @csrf
        @if ($bank->exists) @method('PUT') @endif
        <div class="row">
            <div class="col-md-6"><x-input name="bank_name" label="Bank name" placeholder="e.g. Dutch-Bangla Bank" :value="$bank->bank_name" required maxlength="255" /></div>
            <div class="col-md-6"><x-input name="branch" label="Branch" placeholder="e.g. Motijheel Branch" :value="$bank->branch" maxlength="255" /></div>
            <div class="col-md-6"><x-input name="account_number" label="Account number" placeholder="e.g. 1234 5678 9012" :value="$bank->account_number" required maxlength="60" pattern="[A-Za-z0-9\-\s]+" feedback="Use letters, numbers, spaces and dashes only." /></div>
            <div class="col-md-6">
                @if ($bank->exists)
                    <x-input name="balance_display" label="Current balance" placeholder="Current balance" :value="number_format($bank->current_balance, 2)" disabled help="Changes only through incomes and expenses." />
                @else
                    <x-input name="opening_balance" type="number" label="Current balance" placeholder="0.00" min="0" step="any" help="The balance in this account today." />
                @endif
            </div>
            <div class="col-md-6"><x-select name="status" label="Status" :options="[1 => 'Active', 0 => 'Inactive']" :placeholder="false" :value="(int) $bank->status" required /></div>
        </div>
        <div class="form-actions"><button class="btn btn-primary btn-submit" type="submit">Save Bank Account</button></div>
    </form>
</div></div>
@endsection
