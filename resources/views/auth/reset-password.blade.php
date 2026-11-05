@extends('layouts.guest')
@section('title', 'Reset password')
@section('content')
<form method="POST" action="{{ route('password.update') }}" class="needs-validation" novalidate>
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <x-input name="email" type="email" label="Email" placeholder="you@example.com" :value="$email" required />
    <x-input name="password" type="password" label="New password" placeholder="At least 8 characters" required minlength="8" />
    <x-input name="password_confirmation" type="password" label="Confirm password" placeholder="Repeat the new password" required />
    <div class="form-actions"><button class="btn btn-primary btn-submit" type="submit">Reset password</button></div>
</form>
@endsection
