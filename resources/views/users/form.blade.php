@extends('layouts.app')
@section('title', $user->exists ? 'Edit User' : 'Add User')
@section('content')
<x-page-header :title="$user->exists ? 'Edit User' : 'Add User'"><a class="btn btn-light" href="{{ route('users.index') }}">Back</a></x-page-header>
<form method="POST" enctype="multipart/form-data" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="needs-validation" novalidate autocomplete="off">
    @csrf
    @if ($user->exists) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-lg-8"><div class="card"><div class="card-body"><div class="row">
            <div class="col-md-6"><x-input name="name" label="Full name" placeholder="e.g. Rahim Uddin" :value="$user->name" required maxlength="255" /></div>
            <div class="col-md-6"><x-input name="email" type="email" label="Email" placeholder="user@example.com" :value="$user->email" required maxlength="255" autocomplete="off" /></div>
            <div class="col-md-6"><x-input name="phone" label="Phone" placeholder="e.g. 01712345678" :value="$user->phone" pattern="[0-9+\-\s\(\)]{6,20}" feedback="Enter a valid phone number (6-20 digits)." /></div>
            <div class="col-md-6"><x-select name="role" label="Role" :options="['admin' => 'Admin (full access)', 'user' => 'User (assigned menus only)']" :placeholder="false" :value="$user->role" required /></div>
            <div class="col-md-6"><x-input name="password" type="password" id="password" label="Password" :placeholder="$user->exists ? 'Leave blank to keep the current password' : 'At least 8 characters'" :required="! $user->exists" minlength="8" autocomplete="new-password" feedback="Password must be at least 8 characters." /></div>
            <div class="col-md-6"><x-input name="password_confirmation" type="password" label="Confirm password" placeholder="Repeat the password" :required="! $user->exists" data-match="#password" autocomplete="new-password" feedback="The passwords do not match." /></div>
        </div></div></div></div>
        <div class="col-lg-4"><div class="card"><div class="card-body">
            <x-image-input label="Profile image" :current="$user->image_url" />
            <x-select name="status" label="Status" :options="[1 => 'Active', 0 => 'Inactive']" :placeholder="false" :value="(int) $user->status" required />
            <div class="form-actions"><button class="btn btn-primary btn-submit" type="submit">Save User</button></div>
        </div></div></div>
    </div>
</form>
@endsection
