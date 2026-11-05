@extends('layouts.app')
@section('title', $unit->exists ? 'Edit Unit' : 'Add Unit')
@section('content')
<x-page-header :title="$unit->exists ? 'Edit Unit' : 'Add Unit'">
    <a class="btn btn-light" href="{{ route('units.index') }}">Back</a>
</x-page-header>
<div class="card" style="max-width:640px">
    <div class="card-body">
        <form method="POST" action="{{ $unit->exists ? route('units.update', $unit) : route('units.store') }}" class="needs-validation" novalidate>
            @csrf
            @if ($unit->exists) @method('PUT') @endif
            <x-input name="name" label="Unit name" placeholder="e.g. Piece, Box, Kg" :value="$unit->name" required maxlength="255" />
            <x-input name="short_name" label="Short name" placeholder="e.g. pcs" :value="$unit->short_name" maxlength="20" />
            <x-select name="status" label="Status" :options="[1 => 'Active', 0 => 'Inactive']" :placeholder="false" :value="(int) $unit->status" required />
            <div class="form-actions"><button class="btn btn-primary btn-submit" type="submit">Save</button></div>
        </form>
    </div>
</div>
@endsection
