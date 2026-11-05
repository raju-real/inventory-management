@extends('layouts.app')
@section('title', 'System Settings')
@section('content')
@php $tab = request('tab', 'general'); @endphp
<x-page-header title="System Settings" />
<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link {{ $tab === 'general' ? 'active' : '' }}" href="{{ route('settings.edit') }}">Site</a></li>
    <li class="nav-item"><a class="nav-link {{ $tab === 'mail' ? 'active' : '' }}" href="{{ route('settings.edit', ['tab' => 'mail']) }}">Email (SMTP)</a></li>
</ul>
@if ($tab === 'mail')
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card"><div class="card-header">SMTP server</div><div class="card-body">
                <form method="POST" action="{{ route('settings.mail') }}" class="needs-validation" novalidate autocomplete="off">
                    @csrf @method('PUT')
                    <div class="row">
                        <div class="col-md-8"><x-input name="mail_host" label="SMTP host" placeholder="e.g. smtp.gmail.com" :value="setting('mail_host')" required /></div>
                        <div class="col-md-4"><x-input name="mail_port" type="number" label="Port" placeholder="e.g. 587" :value="setting('mail_port', 587)" required min="1" max="65535" /></div>
                        <div class="col-md-6"><x-input name="mail_username" label="Username" placeholder="SMTP username" :value="setting('mail_username')" autocomplete="off" /></div>
                        <div class="col-md-6"><x-input name="mail_password" type="password" label="Password" :placeholder="setting('mail_password') ? 'Saved. Leave blank to keep it' : 'SMTP password'" autocomplete="new-password" /></div>
                        <div class="col-md-4"><x-select name="mail_encryption" label="Encryption" placeholder="None" :options="['tls' => 'TLS', 'ssl' => 'SSL']" :value="setting('mail_encryption')" /></div>
                        <div class="col-md-4"><x-input name="mail_from_address" type="email" label="From email" placeholder="sales@example.com" :value="setting('mail_from_address')" required /></div>
                        <div class="col-md-4"><x-input name="mail_from_name" label="From name" placeholder="Your business name" :value="setting('mail_from_name')" /></div>
                    </div>
                    <div class="form-actions"><button class="btn btn-primary btn-submit" type="submit">Save Email Settings</button></div>
                </form>
            </div></div>
        </div>
        <div class="col-lg-4">
            <div class="card"><div class="card-header">Send a test email</div><div class="card-body">
                <form method="POST" action="{{ route('settings.mail.test') }}" class="needs-validation" novalidate>
                    @csrf
                    <x-input name="test_email" type="email" label="Send to" placeholder="you@example.com" required />
                    <div class="form-actions"><button class="btn btn-outline-primary btn-submit" type="submit"><i class="bi bi-send me-1"></i>Send test</button></div>
                </form>
            </div></div>
        </div>
    </div>
@else
    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="needs-validation" novalidate>
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-lg-8"><div class="card"><div class="card-body"><div class="row">
                <div class="col-md-8"><x-input name="site_name" label="Site name" placeholder="Your business name" :value="setting('site_name')" required maxlength="100" /></div>
                <div class="col-md-4"><x-input name="currency_symbol" label="Currency symbol" placeholder="e.g. ৳ or $" :value="setting('currency_symbol')" required maxlength="10" /></div>
                <div class="col-md-6"><x-input name="site_email" type="email" label="Email" placeholder="info@example.com" :value="setting('site_email')" maxlength="255" /></div>
                <div class="col-md-6"><x-input name="site_phone" label="Phone" placeholder="e.g. 01712345678" :value="setting('site_phone')" pattern="[0-9+\-\s\(\)]{6,20}" feedback="Enter a valid phone number (6-20 digits)." /></div>
                <div class="col-12"><x-input name="site_address" label="Address" placeholder="Street, area, city" :value="setting('site_address')" maxlength="255" /></div>
                <div class="col-12"><x-input name="site_website" type="url" label="Website" placeholder="https://www.example.com" :value="setting('site_website')" maxlength="255" /></div>
                <div class="col-12"><x-textarea name="invoice_footer" label="Invoice footer" placeholder="Text shown at the bottom of every invoice" :value="setting('invoice_footer')" rows="2" maxlength="500" /></div>
            </div></div></div></div>
            <div class="col-lg-4"><div class="card"><div class="card-body">
                <x-image-input name="site_logo" label="Logo" :current="setting('site_logo') ? asset('storage/' . setting('site_logo')) : null" :removable="false" help="JPG, PNG or WEBP, up to 2 MB. Shown in the menu and on invoices." />
                <x-image-input name="site_favicon" label="Favicon" :current="setting('site_favicon') ? asset('storage/' . setting('site_favicon')) : null" :removable="false" accept=".png,.ico,.jpg,.jpeg" :maxKb="512" help="PNG or ICO, up to 512 KB." />
                <div class="form-actions"><button class="btn btn-primary btn-submit" type="submit">Save Settings</button></div>
            </div></div></div>
        </div>
    </form>
@endif
@endsection
