@extends('layouts.guest')
@section('title', 'Log in')
@section('content')
<form method="POST" action="{{ route('login') }}" class="needs-validation" novalidate>
    @csrf
    <x-input name="email" type="email" label="Email" placeholder="you@example.com" required autofocus autocomplete="username" feedback="Please enter your email address." />
    <x-input name="password" type="password" label="Password" placeholder="Enter your password" required autocomplete="current-password" feedback="Please enter your password." />
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
            <label class="form-check-label" for="remember">Remember me</label>
        </div>
        <a class="small" href="{{ route('password.request') }}">Forgot password?</a>
    </div>
    <div class="form-actions"><button class="btn btn-primary btn-submit" type="submit">Log in</button></div>
</form>
@endsection
