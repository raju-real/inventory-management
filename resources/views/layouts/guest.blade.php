<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - {{ setting('site_name', config('app.name')) }}</title>
    @if (setting('site_favicon'))<link rel="icon" href="{{ asset('storage/' . setting('site_favicon')) }}">@endif
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}">
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <div class="text-center mb-4">
            @if (setting('site_logo'))
                <img src="{{ asset('storage/' . setting('site_logo')) }}" alt="{{ setting('site_name') }}" style="max-height:56px;max-width:200px">
            @else
                <div class="stat-icon tone-teal mx-auto mb-2"><i class="bi bi-box-seam"></i></div>
            @endif
            <h1 class="h5 mt-2 mb-0">{{ setting('site_name', config('app.name')) }}</h1>
        </div>
        @if (session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="alert alert-danger py-2">{{ session('error') }}</div>@endif
        @yield('content')
    </div>
</div>
<script src="{{ asset('assets/js/forms.js') }}"></script>
</body>
</html>
