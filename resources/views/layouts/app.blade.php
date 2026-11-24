<!DOCTYPE html>
<html lang="en" data-sidebar-key="sidebar-collapsed:{{ auth()->user()->unique_id }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - {{ setting('site_name', config('app.name')) }}</title>
    @if (setting('site_favicon'))<link rel="icon" href="{{ asset('storage/' . setting('site_favicon')) }}">@endif
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/datepicker/bootstrap-datepicker3.standalone.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}">
    @stack('styles')
    <script>
        // Runs before the page paints, so the remembered sidebar state never flickers.
        try {
            if (localStorage.getItem(document.documentElement.dataset.sidebarKey) === '1') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch (e) { /* storage blocked: the sidebar simply starts open */ }
    </script>
</head>
<body>
<aside class="sidebar">
    <a class="sidebar-brand" href="{{ route('home') }}">
        @if (setting('site_logo'))
            <img src="{{ asset('storage/' . setting('site_logo')) }}" alt="{{ setting('site_name') }}">
        @else
            <span class="mark"><i class="bi bi-box-seam"></i></span>
            <span class="text-truncate">{{ setting('site_name', config('app.name')) }}</span>
        @endif
    </a>
    <nav class="sidebar-nav">
        @foreach ($sidebarMenus as $menu)
            @if ($menu->children->isEmpty())
                <div class="side-item">
                    @include('partials.side-link', ['item' => $menu])
                </div>
            @else
                @php
                    $isOpen = $menu->children->contains(fn ($child) => request()->routeIs($child->route)
                        || (str_ends_with($child->route, '.index') && $child->key !== 'stock' && request()->routeIs(str_replace('.index', '.*', $child->route))));
                @endphp
                <div class="side-item">
                    <button type="button" class="side-link {{ $isOpen ? 'has-active' : '' }}" data-bs-toggle="collapse" data-bs-target="#menu-{{ $menu->unique_id }}" aria-expanded="{{ $isOpen ? 'true' : 'false' }}" aria-label="{{ $menu->name }}">
                        <i class="bi {{ $menu->icon }}"></i><span>{{ $menu->name }}</span><i class="bi bi-chevron-right chev"></i>
                    </button>
                    <div class="collapse side-sub {{ $isOpen ? 'show' : '' }}" id="menu-{{ $menu->unique_id }}">
                        <div class="side-flyout-title">{{ $menu->name }}</div>
                        @foreach ($menu->children as $child)
                            @include('partials.side-link', ['item' => $child])
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </nav>
</aside>
<div class="sidebar-backdrop"></div>

<div class="main">
    <header class="topbar">
        <button class="btn btn-light" type="button" data-sidebar-toggle aria-label="Toggle menu" title="Hide or show the menu"><i class="bi bi-list"></i></button>
        @foreach ([
            ['sales', 'sales.create', 'New Sale', 'bi-receipt', 'btn-primary', 'F4'],
            ['purchases', 'purchases.create', 'New Purchase', 'bi-bag-plus', 'btn-outline-primary', 'F5'],
            ['incomes', 'incomes.create', 'New Income', 'bi-arrow-down-left-circle', 'btn-outline-primary', null],
            ['expenses', 'expenses.create', 'New Expense', 'bi-arrow-up-right-circle', 'btn-outline-primary', null],
        ] as [$menuKey, $routeName, $text, $icon, $style, $shortcut])
            @access($menuKey, 'create')
                <a class="btn {{ $style }} btn-submit" href="{{ route($routeName) }}" title="{{ $text }}{{ $shortcut ? ' (' . $shortcut . ')' : '' }}" @if ($shortcut) data-shortcut="{{ $shortcut }}" aria-keyshortcuts="{{ $shortcut }}" @endif><i class="bi {{ $icon }}"></i><span class="d-none d-xl-inline ms-1">{{ $text }}</span></a>
            @endaccess
        @endforeach
        <div class="ms-auto d-flex align-items-center gap-2">
            @if (setting('barcode_enabled', '1') === '1')
                <span class="scan-status d-none d-xxl-inline-flex" title="A connected barcode scanner works on this page"><span class="dot"></span> Scanner ready</span>
            @endif
            @if (auth()->user()->canAccess('report-low-stock') || auth()->user()->canAccess('products'))
                <a class="btn btn-light position-relative" title="Low stock products" aria-label="Low stock products"
                   href="{{ auth()->user()->canAccess('report-low-stock') ? route('reports.low-stock') : route('products.index', ['low_stock' => 1]) }}">
                    <i class="bi bi-bell"></i>
                    @if ($lowStockCount > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $lowStockCount }}</span>
                    @endif
                </a>
            @endif
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" type="button">
                    @if (auth()->user()->image_url)
                        <img src="{{ auth()->user()->image_url }}" alt="" class="rounded-circle" style="width:24px;height:24px;object-fit:cover">
                    @else
                        <i class="bi bi-person-circle"></i>
                    @endif
                    <span class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text small text-muted text-capitalize">{{ auth()->user()->role }}</span></li>
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <button class="dropdown-item text-danger btn-submit" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Log out</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <main class="content">
        @yield('content')
    </main>
    <footer class="footer">&copy; {{ date('Y') }} {{ setting('site_name', config('app.name')) }}</footer>
</div>

<div class="toast-area">
    @if (session('success'))<div class="alert alert-success alert-dismissible fade show shadow-sm mb-2" data-autohide role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if (session('error'))<div class="alert alert-danger alert-dismissible fade show shadow-sm mb-2" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @foreach (['category', 'unit', 'product', 'customer', 'supplier', 'user', 'purchase', 'sale', 'return', 'damage', 'adjustment', 'payment', 'cancel_reason', 'stock', 'bank_account', 'entry'] as $errorKey)
        @error($errorKey)<div class="alert alert-danger alert-dismissible fade show shadow-sm mb-2" role="alert">{{ $message }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@enderror
    @endforeach
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST">@csrf @method('DELETE')
            <div class="modal-header"><h2 class="modal-title h5">Confirm delete</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">Delete <strong data-name></strong>? This cannot be undone.</div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Keep it</button><button type="submit" class="btn btn-danger btn-submit">Delete</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content needs-validation" method="POST" novalidate>@csrf
            <div class="modal-header"><h2 class="modal-title h5">Cancel <span data-name></span></h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <p class="text-muted small">Stock and balances are reversed by new entries. The original record stays in the history.</p>
                <label class="form-label" for="cancel_reason">Reason <span class="req">*</span></label>
                <input class="form-control" id="cancel_reason" name="cancel_reason" placeholder="Why is this being cancelled?" maxlength="255" required>
                <div class="invalid-feedback">Please enter a reason.</div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Keep it</button><button type="submit" class="btn btn-danger btn-submit">Cancel document</button></div>
        </form>
    </div>
</div>

<script>
    window.APP = {
        scanner: {
            enabled: {{ setting('barcode_enabled', '1') === '1' ? 'true' : 'false' }},
            minLength: {{ (int) setting('barcode_min_length', 4) }},
            maxInterval: {{ (int) setting('barcode_max_interval', 50) }},
            suffix: @json(setting('barcode_suffix', 'enter')),
            beep: {{ setting('barcode_beep', '1') === '1' ? 'true' : 'false' }},
        },
    };
</script>
<script src="{{ asset('assets/vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('assets/vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/vendor/datepicker/bootstrap-datepicker.min.js') }}"></script>
<script src="{{ asset('assets/js/forms.js') }}"></script>
<script src="{{ asset('assets/js/app.js') }}"></script>
<script src="{{ asset('assets/js/scanner.js') }}"></script>
@stack('scripts')
</body>
</html>
