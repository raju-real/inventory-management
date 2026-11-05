@extends('layouts.app')
@section('title', 'Users')
@section('content')
<x-page-header title="Users" sub="Admins can open everything. Other users see only the menus you assign under Manage Access.">
    <a class="btn btn-primary btn-submit" href="{{ route('users.create') }}"><i class="bi bi-plus-lg me-1"></i>Add User</a>
</x-page-header>
<x-filter search="Search by name, email or phone">
    <x-filter-select name="role" label="Role" :options="['admin' => 'Admin', 'user' => 'User']" />
    <x-filter-select name="status" label="Status" :options="[1 => 'Active', 0 => 'Inactive']" />
</x-filter>
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>User</th><th>Email</th><th>Phone</th><th>Role</th><th>Assigned menus</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($users as $user)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            @if ($user->image_url)<img class="thumb" src="{{ $user->image_url }}" alt="">@else<span class="thumb"><i class="bi bi-person"></i></span>@endif
                            <span class="fw-medium">{{ $user->name }}</span>
                        </div>
                    </td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->phone }}</td>
                    <td><span class="badge {{ $user->isAdmin() ? 'tone-violet' : 'tone-blue' }} text-capitalize">{{ $user->role }}</span></td>
                    <td>{{ $user->isAdmin() ? 'All menus' : $user->menu_permissions_count }}</td>
                    <td><x-status :active="$user->status" /></td>
                    <td class="table-actions">
                        @unless ($user->isAdmin())
                            <a class="btn btn-sm btn-primary" href="{{ route('users.access', $user) }}"><i class="bi bi-shield-check me-1"></i>Manage Access</a>
                        @endunless
                        <a class="btn btn-sm btn-light" title="Edit" href="{{ route('users.edit', $user) }}"><i class="bi bi-pencil"></i></a>
                        @if ($user->id !== auth()->id())
                            <button class="btn btn-sm btn-light text-danger" title="Delete" data-delete-url="{{ route('users.destroy', $user) }}" data-name="{{ $user->name }}"><i class="bi bi-trash"></i></button>
                        @endif
                    </td>
                </tr>
            @empty
                <x-empty-row :cols="7" />
            @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :items="$users" />
</div>
@endsection
