@extends('layouts.app')
@section('title', 'Profile')
@section('content')
<x-page-header title="My Profile" />
<form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="needs-validation" novalidate autocomplete="off">
    @csrf @method('PUT')
    <div class="row g-3">
        <div class="col-lg-8"><div class="card"><div class="card-body"><div class="row">
            <div class="col-md-6"><x-input name="name" label="Full name" placeholder="Your name" :value="$user->name" required maxlength="255" /></div>
            <div class="col-md-6"><x-input name="email" type="email" label="Email" placeholder="you@example.com" :value="$user->email" required maxlength="255" /></div>
            <div class="col-md-6"><x-input name="phone" label="Phone" placeholder="e.g. 01712345678" :value="$user->phone" pattern="[0-9+\-\s\(\)]{6,20}" feedback="Enter a valid phone number (6-20 digits)." /></div>
            <div class="col-12"><hr><h2 class="h6">Change password</h2><p class="small text-muted">Leave these blank to keep your current password.</p></div>
            <div class="col-md-4"><x-input name="current_password" type="password" label="Current password" placeholder="Current password" autocomplete="current-password" /></div>
            <div class="col-md-4"><x-input name="password" type="password" id="password" label="New password" placeholder="At least 8 characters" minlength="8" autocomplete="new-password" feedback="Password must be at least 8 characters." /></div>
            <div class="col-md-4"><x-input name="password_confirmation" type="password" label="Confirm password" placeholder="Repeat the new password" data-match="#password" autocomplete="new-password" feedback="The passwords do not match." /></div>
        </div></div></div></div>
        <div class="col-lg-4"><div class="card"><div class="card-body">
            <x-image-input label="Profile image" :current="$user->image_url" />
            <div class="form-actions"><button class="btn btn-primary btn-submit" type="submit">Save Profile</button></div>
        </div></div></div>
    </div>
</form>
@endsection
