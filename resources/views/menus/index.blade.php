@extends('layouts.app')
@section('title', 'Menu Manager')
@section('content')
<x-page-header title="Menu Manager" sub="Rename, reorder, move or hide menus and submenus. Choose what each user can open from Users > Manage Access." />
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Menu</th><th>Under</th><th class="text-end">Order</th><th>Visibility</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach ($menus as $menu)
                <tr>
                    <td class="{{ $menu->parent_id ? 'ps-4' : 'fw-semibold' }}"><i class="bi {{ $menu->icon }} me-2"></i>{{ $menu->name }}</td>
                    <td>{{ $menu->parent?->name ?? 'Main menu' }}</td>
                    <td class="num">{{ $menu->sort_order }}</td>
                    <td>{{ $menu->admin_only ? 'Admins only' : ($menu->is_public ? 'Everyone' : (! $menu->route ? 'Group' : 'Assigned users')) }}</td>
                    <td><x-status :active="$menu->is_active" /></td>
                    <td class="table-actions"><a class="btn btn-sm btn-primary" href="{{ route('menus.edit', $menu) }}"><i class="bi bi-gear me-1"></i>Manage</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <x-pagination :items="$menus" />
</div>
@endsection
