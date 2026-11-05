@extends('layouts.app')
@section('title', 'Receive Payment')
@section('content')
<x-page-header title="Receive Customer Payment"><a class="btn btn-light" href="{{ route('customer-payments.index') }}">Back</a></x-page-header>
<div class="card" style="max-width:640px"><div class="card-body">
    @if ($sale)
        <div class="alert alert-light border">Invoice <strong>{{ $sale->reference_no }}</strong>: due {{ money($sale->due_amount) }}</div>
    @endif
    <form method="POST" action="{{ route('customer-payments.store') }}" class="needs-validation" novalidate>
        @csrf
        @if ($sale)<input type="hidden" name="sale_id" value="{{ $sale->unique_id }}">@endif
        <x-select name="customer_id" label="Customer" :options="$customers->pluck('name', 'unique_id')" :value="$customerId" required />
        <div class="row">
            <div class="col-md-6"><x-input name="payment_date" type="date" label="Payment date" placeholder="Select date" :value="now()->toDateString()" required /></div>
            <div class="col-md-6"><x-input name="amount" type="number" label="Amount" placeholder="0.00" :value="$sale ? (float) $sale->due_amount : null" required min="0.01" step="any" :max="$sale ? (float) $sale->due_amount : null" /></div>
            <div class="col-md-6"><x-select name="method" label="Payment method" :options="['cash' => 'Cash', 'bank' => 'Bank', 'card' => 'Card', 'mobile' => 'Mobile banking', 'other' => 'Other']" :placeholder="false" value="cash" required /></div>
            <div class="col-md-6"><x-input name="transaction_ref" label="Transaction reference" placeholder="Cheque or transaction ID (optional)" maxlength="255" /></div>
        </div>
        <x-textarea name="notes" label="Notes" placeholder="Notes (optional)" rows="2" maxlength="1000" />
        <div class="form-actions"><button class="btn btn-primary btn-submit" type="submit">Save Payment</button></div>
    </form>
</div></div>
@endsection
