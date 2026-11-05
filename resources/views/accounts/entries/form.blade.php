@extends('layouts.app')
@section('title', ($entry->exists ? 'Edit ' : 'Add ') . $word)
@section('content')
<x-page-header :title="($entry->exists ? 'Edit ' : 'Add ') . $word" :sub="$effect"><a class="btn btn-light" href="{{ route($prefix . '.index') }}">Back</a></x-page-header>
@php
    $canAddCategory = auth()->user()->canAccess($categoryMenu, 'create');
    // A missing category is not a problem for someone who can add one with the + button.
    $needsCategory = $categories->isEmpty() && ! $canAddCategory;
@endphp
@if ($banks->isEmpty() || $needsCategory)
    <div class="alert alert-warning">
        Before adding this, you need at least one
        @if ($banks->isEmpty())
            @access('bank-accounts', 'view')<a href="{{ route('bank-accounts.index') }}">bank account</a>@else bank account @endaccess
        @endif
        @if ($banks->isEmpty() && $needsCategory) and one @endif
        @if ($needsCategory) category @endif.
    </div>
@endif
<div class="card" style="max-width:720px"><div class="card-body">
    <form method="POST" action="{{ $entry->exists ? route($prefix . '.update', $entry) : route($prefix . '.store') }}" class="needs-validation" novalidate autocomplete="off">
        @csrf
        @if ($entry->exists) @method('PUT') @endif
        <div class="row">
            <div class="col-md-6"><x-input name="entry_date" type="date" label="Date" placeholder="Select date" :value="$entry->entry_date?->toDateString() ?? now()->toDateString()" required /></div>
            <div class="col-md-6"><x-input name="amount" type="number" label="Amount" placeholder="0.00" :value="$entry->amount" required min="0.01" step="any" /></div>
            <div class="col-md-6"><x-select name="category_id" label="Category" :options="$categories->pluck('name', 'unique_id')" :value="$entry->category?->unique_id" required>
                @if ($canAddCategory)
                    <x-slot name="labelAction">
                        <button type="button" class="btn btn-sm btn-outline-primary quick-add-btn" data-bs-toggle="modal" data-bs-target="#categoryModal" title="Add new category" aria-label="Add new category"><i class="bi bi-plus-lg"></i></button>
                    </x-slot>
                @endif
            </x-select></div>
            <div class="col-md-6"><x-select name="bank_account_id" label="Bank account" :options="$banks->mapWithKeys(fn ($bank) => [$bank->unique_id => $bank->label . ' (' . money($bank->current_balance) . ')'])" :value="$entry->bankAccount?->unique_id" required /></div>
            <div class="col-12"><x-input name="reference" label="Reference" placeholder="Voucher or cheque number (optional)" :value="$entry->reference" maxlength="100" /></div>
            <div class="col-12"><x-textarea name="notes" label="Notes" placeholder="Notes (optional)" :value="$entry->notes" rows="2" maxlength="1000" /></div>
        </div>
        <div class="form-actions"><button class="btn btn-primary btn-submit" type="submit">Save {{ $word }}</button></div>
    </form>
</div></div>
@if ($canAddCategory)
    @include('accounts.entries._category-modal')
@endif
@endsection
@if ($canAddCategory)
    @push('scripts')
    <script src="{{ asset('assets/js/quick-add.js') }}"></script>
    @endpush
@endif
