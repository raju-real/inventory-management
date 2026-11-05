@extends('layouts.app')
@section('title', $customer->exists ? 'Edit Customer' : 'Add Customer')
@section('content')
<x-page-header :title="$customer->exists ? 'Edit Customer' : 'Add Customer'">
    <a class="btn btn-light" href="{{ route('customers.index') }}">Back</a>
</x-page-header>
<form method="POST" enctype="multipart/form-data" action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}" class="needs-validation" novalidate>
    @csrf
    @if ($customer->exists) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-lg-8"><div class="card"><div class="card-body"><div class="row">
            <div class="col-12"><x-input name="name" label="Customer name" placeholder="e.g. Rahim Uddin" :value="$customer->name" required maxlength="255" /></div>
            <div class="col-md-6"><x-input name="mobile" label="Mobile number" placeholder="e.g. 01712345678" :value="$customer->mobile" pattern="[0-9+\-\s\(\)]{6,20}" feedback="Enter a valid mobile number (6-20 digits)." /></div>
            <div class="col-md-6"><x-input name="email" type="email" label="Email" placeholder="customer@example.com" :value="$customer->email" maxlength="255" /></div>
            <div class="col-12"><x-input name="address" label="Address" placeholder="Street, area, city" :value="$customer->address" maxlength="255" /></div>
            <div class="col-md-6"><x-input name="opening_balance" type="number" label="Opening balance" placeholder="0.00" :value="$customer->exists ? $customer->opening_balance : null" step="any" help="Amount the customer already owes. Use a negative value for an advance." /></div>
        </div></div></div></div>
        <div class="col-lg-4"><div class="card"><div class="card-body">
            <x-image-input label="Customer image" :current="$customer->image_url" />
            <x-select name="status" label="Status" :options="[1 => 'Active', 0 => 'Inactive']" :placeholder="false" :value="(int) $customer->status" required />
            <div class="form-actions"><button class="btn btn-primary btn-submit" type="submit">Save Customer</button></div>
        </div></div></div>
    </div>
</form>
@endsection
