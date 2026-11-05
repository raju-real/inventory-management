@extends('layouts.app')
@section('title', ($category->exists ? 'Edit ' : 'Add ') . $word)
@section('content')
<x-page-header :title="($category->exists ? 'Edit ' : 'Add ') . $word"><a class="btn btn-light" href="{{ route($prefix . '.index') }}">Back</a></x-page-header>
<div class="card" style="max-width:640px"><div class="card-body">
    <form method="POST" action="{{ $category->exists ? route($prefix . '.update', $category) : route($prefix . '.store') }}" class="needs-validation" novalidate>
        @csrf
        @if ($category->exists) @method('PUT') @endif
        <x-input name="name" label="Category name" :placeholder="$example" :value="$category->name" required maxlength="255" />
        <x-input name="description" label="Description" placeholder="Short description (optional)" :value="$category->description" maxlength="255" />
        <x-select name="status" label="Status" :options="[1 => 'Active', 0 => 'Inactive']" :placeholder="false" :value="(int) $category->status" required />
        <div class="form-actions"><button class="btn btn-primary btn-submit" type="submit">Save</button></div>
    </form>
</div></div>
@endsection
