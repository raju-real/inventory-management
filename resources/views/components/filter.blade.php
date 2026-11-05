@props(['action' => null, 'scan' => false, 'search' => null])
<form method="GET" action="{{ $action ?? url()->current() }}" class="card card-body mb-3 no-print" @if ($scan) data-scan-search @endif>
    <div class="row g-2 align-items-end">
        @if ($search)
            <div class="col-md-4">
                <label class="form-label small" for="search">Search</label>
                <input type="search" class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="{{ $search }}" autocomplete="off">
            </div>
        @endif
        {{ $slot }}
        <div class="col-auto ms-auto">
            <a class="btn btn-light" href="{{ $action ?? url()->current() }}">Reset</a>
            <button class="btn btn-primary btn-submit" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button>
        </div>
    </div>
</form>
