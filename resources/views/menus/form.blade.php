@extends('layouts.app')
@section('title', 'Manage Menu')
@section('content')
<x-page-header :title="'Manage Menu: ' . $menu->name"><a class="btn btn-light" href="{{ route('menus.index') }}">Back</a></x-page-header>
<div class="card" style="max-width:640px"><div class="card-body">
    <form method="POST" action="{{ route('menus.update', $menu) }}" class="needs-validation" novalidate>
        @csrf @method('PUT')
        <x-input name="name" label="Menu name" placeholder="e.g. Products" :value="$menu->name" required maxlength="100" />
        @if ($menu->route)
            <x-select name="parent_id" label="Show under" placeholder="Main menu (no parent)" :options="$parents" :value="$menu->parent?->unique_id" />
        @endif
        <div class="row">
            <div class="col-md-6"><x-input name="icon" label="Icon" placeholder="e.g. bi-box" :value="$menu->icon" required pattern="bi-[a-z0-9\-]+" help="A Bootstrap Icons class name." feedback="Use an icon class like bi-box." /></div>
            <div class="col-md-6"><x-input name="sort_order" type="number" label="Order" placeholder="e.g. 1" :value="$menu->sort_order" required min="0" max="9999" /></div>
        </div>
        <x-select name="is_active" label="Status" :options="[1 => 'Active', 0 => 'Hidden']" :placeholder="false" :value="(int) $menu->is_active" required />
        <div class="form-actions"><button class="btn btn-primary btn-submit" type="submit">Save</button></div>
    </form>
</div></div>
@endsection
