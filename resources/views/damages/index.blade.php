@extends('layouts.app')
@section('title', 'Damage Management')
@section('content')
<x-page-header title="Damage Management">
    @access('damages', 'create')<a class="btn btn-primary btn-submit" href="{{ route('damages.create') }}"><i class="bi bi-plus-lg me-1"></i>Record Damage</a>@endaccess
</x-page-header>
<x-filter search="Search by reference number">
    <x-filter-select name="status" label="Status" :options="['posted' => 'Posted', 'cancelled' => 'Cancelled']" />
    <x-filter-dates />
</x-filter>
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Reference</th><th>Date</th><th class="text-end">Products</th><th class="text-end">Quantity</th><th>Recorded by</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($damages as $damage)
                <tr>
                    <td><a class="fw-medium text-decoration-none" href="{{ route('damages.show', $damage) }}">{{ $damage->reference_no }}</a></td>
                    <td>{{ $damage->damage_date->format('d M Y') }}</td>
                    <td class="num">{{ $damage->details_count }}</td>
                    <td class="num">{{ qty($damage->total_quantity) }}</td>
                    <td>{{ $damage->creator?->name }}</td>
                    <td><x-doc-status :status="$damage->status" /></td>
                    <td class="table-actions"><a class="btn btn-sm btn-light" title="View" href="{{ route('damages.show', $damage) }}"><i class="bi bi-eye"></i></a></td>
                </tr>
            @empty
                <x-empty-row :cols="7" />
            @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :items="$damages" />
</div>
@endsection
