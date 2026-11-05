@extends('layouts.guest')
@section('title', 'Forgot password')
@section('content')
<p class="text-muted small">Enter your email address and we will send you a link to reset your password.</p>
<form method="POST" action="{{ route('password.email') }}" class="needs-validation" novalidate>
    @csrf
    <x-input name="email" type="email" label="Email" placeholder="you@example.com" required autofocus feedback="Please enter your email address." />
    <div class="form-actions"><button class="btn btn-primary btn-submit" type="submit">Send reset link</button></div>
    <a class="d-block text-center small mt-3" href="{{ route('login') }}">Back to log in</a>
</form>
@endsection
