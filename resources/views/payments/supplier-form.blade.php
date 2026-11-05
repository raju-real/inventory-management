@extends('layouts.app')
@section('title', 'Make Payment')
@section('content')
<x-page-header title="Make Supplier Payment"><a class="btn btn-light" href="{{ route('supplier-payments.index') }}">Back</a></x-page-header>
<div class="card" style="max-width:640px"><div class="card-body">
    @if ($purchase)
        <div class="alert alert-light border">Invoice <strong>{{ $purchase->reference_no }}</strong>: due {{ money($purchase->due_amount) }}</div>
    @endif
    <form method="POST" action="{{ route('supplier-payments.store') }}" class="needs-validation" novalidate>
        @csrf
        @if ($purchase)<input type="hidden" name="purchase_id" value="{{ $purchase->unique_id }}">@endif
        <x-select name="supplier_id" label="Supplier" :options="$suppliers->pluck('name', 'unique_id')" :value="$supplierId" required />
        <div class="row">
            <div class="col-md-6"><x-input name="payment_date" type="date" label="Payment date" placeholder="Select date" :value="now()->toDateString()" required /></div>
            <div class="col-md-6"><x-input name="amount" type="number" label="Amount" placeholder="0.00" :value="$purchase ? (float) $purchase->due_amount : null" required min="0.01" step="any" :max="$purchase ? (float) $purchase->due_amount : null" /></div>
            <div class="col-md-6"><x-select name="method" label="Payment method" :options="['cash' => 'Cash', 'bank' => 'Bank', 'card' => 'Card', 'mobile' => 'Mobile banking', 'other' => 'Other']" :placeholder="false" value="cash" required /></div>
            <div class="col-md-6"><x-input name="transaction_ref" label="Transaction reference" placeholder="Cheque or transaction ID (optional)" maxlength="255" /></div>
        </div>
        <x-textarea name="notes" label="Notes" placeholder="Notes (optional)" rows="2" maxlength="1000" />
        <div class="form-actions"><button class="btn btn-primary btn-submit" type="submit">Save Payment</button></div>
    </form>
</div></div>
@endsection
