@extends('layouts.app')
@section('title', $title)
@section('content')
<x-page-header :title="$title">
    @access($menuKey, 'create')<a class="btn btn-primary btn-submit" href="{{ route($prefix . '.create') }}"><i class="bi bi-plus-lg me-1"></i>Add {{ $word }}</a>@endaccess
</x-page-header>
<x-filter search="Search by reference or notes">
    <x-filter-select name="category_id" label="Category" :options="$categories->pluck('name', 'unique_id')" />
    <x-filter-select name="bank_account_id" label="Bank account" :options="$banks->pluck('label', 'unique_id')" />
    <x-filter-dates />
</x-filter>
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-4"><div class="stat-card"><div class="stat-icon {{ $tone === 'in' ? 'tone-green' : 'tone-red' }}"><i class="bi {{ $tone === 'in' ? 'bi-arrow-down-left' : 'bi-arrow-up-right' }}"></i></div><div><div class="stat-label">Total {{ strtolower($word) }} shown</div><div class="stat-value">{{ money($total) }}</div></div></div></div>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Date</th><th>Category</th><th>Bank account</th><th>Reference</th><th>Notes</th><th class="text-end">Amount</th><th>By</th><th></th></tr></thead>
            <tbody>
            @forelse ($rows as $entry)
                <tr>
                    <td class="text-nowrap">{{ $entry->entry_date->format('d M Y') }}</td>
                    <td class="fw-medium">{{ $entry->category?->name }}</td>
                    <td>{{ $entry->bankAccount?->bank_name }}<div class="small text-muted">{{ $entry->bankAccount?->account_number }}</div></td>
                    <td>{{ $entry->reference }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($entry->notes, 60) }}</td>
                    <td class="num {{ $tone }}">{{ number_format($entry->amount, 2) }}</td>
                    <td>{{ $entry->creator?->name }}</td>
                    <td class="table-actions">
                        @access($menuKey, 'edit')<a class="btn btn-sm btn-light" title="Edit" href="{{ route($prefix . '.edit', $entry) }}"><i class="bi bi-pencil"></i></a>@endaccess
                        @access($menuKey, 'delete')<button class="btn btn-sm btn-light text-danger" title="Delete" data-delete-url="{{ route($prefix . '.destroy', $entry) }}" data-name="this {{ strtolower($word) }} of {{ number_format($entry->amount, 2) }}"><i class="bi bi-trash"></i></button>@endaccess
                    </td>
                </tr>
            @empty
                <x-empty-row :cols="8" />
            @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :items="$rows" />
</div>
@endsection
