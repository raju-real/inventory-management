{{-- Shared list of sales returns and purchase returns. Needs: $title, $returns, $prefix, $menuKey, $partyLabel, $partyField, $invoiceField, $newLabel --}}
<x-page-header :title="$title">
    @access($menuKey, 'create')<a class="btn btn-primary btn-submit" href="{{ route($prefix . '.create') }}"><i class="bi bi-plus-lg me-1"></i>{{ $newLabel }}</a>@endaccess
</x-page-header>
<x-filter search="Search by return, invoice or {{ strtolower($partyLabel) }}">
    <x-filter-select name="status" label="Status" :options="['posted' => 'Posted', 'cancelled' => 'Cancelled']" />
    <x-filter-dates />
</x-filter>
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Return</th><th>Invoice</th><th>Date</th><th>{{ $partyLabel }}</th><th class="text-end">Quantity</th><th class="text-end">Value</th><th class="text-end">Refund</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($returns as $return)
                <tr>
                    <td><a class="fw-medium text-decoration-none" href="{{ route($prefix . '.show', $return) }}">{{ $return->reference_no }}</a></td>
                    <td>{{ $return->{$invoiceField}?->reference_no }}</td>
                    <td>{{ $return->return_date->format('d M Y') }}</td>
                    <td>{{ $return->{$partyField}?->name }}</td>
                    <td class="num">{{ qty($return->total_quantity) }}</td>
                    <td class="num">{{ number_format($return->total_amount, 2) }}</td>
                    <td class="num">{{ number_format($return->refund_amount, 2) }}</td>
                    <td><x-doc-status :status="$return->status" /></td>
                    <td class="table-actions"><a class="btn btn-sm btn-light" title="View" href="{{ route($prefix . '.show', $return) }}"><i class="bi bi-eye"></i></a></td>
                </tr>
            @empty
                <x-empty-row :cols="9" />
            @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :items="$returns" />
</div>
