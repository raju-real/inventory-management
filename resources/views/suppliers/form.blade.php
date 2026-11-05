@extends('layouts.app')
@section('title', $supplier->exists ? 'Edit Supplier' : 'Add Supplier')
@section('content')
<x-page-header :title="$supplier->exists ? 'Edit Supplier' : 'Add Supplier'">
    <a class="btn btn-light" href="{{ route('suppliers.index') }}">Back</a>
</x-page-header>
<form method="POST" enctype="multipart/form-data" action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}" class="needs-validation" novalidate>
    @csrf
    @if ($supplier->exists) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-lg-8"><div class="card"><div class="card-body"><div class="row">
            <div class="col-md-6"><x-input name="name" label="Supplier name" placeholder="e.g. ABC Traders" :value="$supplier->name" required maxlength="255" /></div>
            <div class="col-md-6"><x-input name="contact_person" label="Contact person" placeholder="e.g. Karim Ahmed" :value="$supplier->contact_person" maxlength="255" /></div>
            <div class="col-md-6"><x-input name="mobile" label="Mobile number" placeholder="e.g. 01712345678" :value="$supplier->mobile" pattern="[0-9+\-\s\(\)]{6,20}" feedback="Enter a valid mobile number (6-20 digits)." /></div>
            <div class="col-md-6"><x-input name="email" type="email" label="Email" placeholder="supplier@example.com" :value="$supplier->email" maxlength="255" /></div>
            <div class="col-12"><x-input name="address" label="Address" placeholder="Street, area, city" :value="$supplier->address" maxlength="255" /></div>
            <div class="col-md-6"><x-input name="opening_balance" type="number" label="Opening balance" placeholder="0.00" :value="$supplier->exists ? $supplier->opening_balance : null" step="any" help="Amount you already owe this supplier. Use a negative value for an advance." /></div>
        </div></div></div></div>
        <div class="col-lg-4"><div class="card"><div class="card-body">
            <x-image-input label="Supplier image" :current="$supplier->image_url" />
            <x-select name="status" label="Status" :options="[1 => 'Active', 0 => 'Inactive']" :placeholder="false" :value="(int) $supplier->status" required />
            <div class="form-actions"><button class="btn btn-primary btn-submit" type="submit">Save Supplier</button></div>
        </div></div></div>
    </div>
</form>
@endsection
