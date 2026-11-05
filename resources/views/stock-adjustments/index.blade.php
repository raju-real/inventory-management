@extends('layouts.app')
@section('title', 'Stock Adjustments')
@section('content')
<x-page-header title="Stock Adjustments">
    @access('stock-adjustments', 'create')<a class="btn btn-primary btn-submit" href="{{ route('stock-adjustments.create') }}"><i class="bi bi-plus-lg me-1"></i>New Adjustment</a>@endaccess
</x-page-header>
<x-filter search="Search by reference or product">
    <x-filter-select name="type" label="Type" :options="['increase' => 'Increase', 'decrease' => 'Decrease']" />
</x-filter>
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Reference</th><th>Date</th><th>Product</th><th>Type</th><th class="text-end">Quantity</th><th>Reason</th><th>By</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($adjustments as $adjustment)
                <tr>
                    <td class="fw-medium">{{ $adjustment->reference_no }}</td>
                    <td>{{ $adjustment->adjustment_date->format('d M Y') }}</td>
                    <td>{{ $adjustment->product?->name }}</td>
                    <td><span class="badge {{ $adjustment->type === 'increase' ? 'tone-green' : 'tone-amber' }}">{{ ucfirst($adjustment->type) }}</span></td>
                    <td class="num">{{ qty($adjustment->quantity) }} {{ $adjustment->product?->unit?->short_name }}</td>
                    <td>{{ $adjustment->reason }}</td>
                    <td>{{ $adjustment->creator?->name }}</td>
                    <td><x-doc-status :status="$adjustment->status" /></td>
                    <td class="table-actions">
                        @unless ($adjustment->isCancelled())
                            @access('stock-adjustments', 'delete')<button class="btn btn-sm btn-light text-danger" title="Cancel" data-cancel-url="{{ route('stock-adjustments.cancel', $adjustment) }}" data-name="{{ $adjustment->reference_no }}"><i class="bi bi-x-circle"></i></button>@endaccess
                        @endunless
                    </td>
                </tr>
            @empty
                <x-empty-row :cols="9" />
            @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :items="$adjustments" />
</div>
@endsection
