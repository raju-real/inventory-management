{{-- Shared list of purchases and sales. Needs: $title, $rows, $prefix (route), $menuKey, $partyLabel, $partyField, $dateField, $parties, $searchHint, $newLabel --}}
<x-page-header :title="$title">
    @access($menuKey, 'create')<a class="btn btn-primary btn-submit" href="{{ route($prefix . '.create') }}"><i class="bi bi-plus-lg me-1"></i>{{ $newLabel }}</a>@endaccess
</x-page-header>
<x-filter :search="$searchHint">
    <x-filter-select name="party_id" :label="$partyLabel" :options="$parties->pluck('name', 'unique_id')" />
    <x-filter-select name="status" label="Status" :options="['posted' => 'Posted', 'cancelled' => 'Cancelled']" />
    <x-filter-dates />
</x-filter>
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Invoice</th><th>Date</th><th>{{ $partyLabel }}</th><th class="text-end">Quantity</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Due</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td><a class="fw-medium text-decoration-none" href="{{ route($prefix . '.show', $row) }}">{{ $row->reference_no }}</a></td>
                    <td>{{ $row->{$dateField}->format('d M Y') }}</td>
                    <td>{{ $row->{$partyField}?->name }}</td>
                    <td class="num">{{ qty($row->total_quantity) }}</td>
                    <td class="num">{{ number_format($row->grand_total, 2) }}</td>
                    <td class="num">{{ number_format($row->paid_amount, 2) }}</td>
                    <td class="num {{ (float) $row->due_amount > 0 ? 'out' : '' }}">{{ number_format($row->due_amount, 2) }}</td>
                    <td>
                        <x-doc-status :status="$row->status" />
                        @unless ($row->isCancelled())<x-pay-status :status="$row->payment_status" />@endunless
                    </td>
                    <td class="table-actions">
                        <a class="btn btn-sm btn-light" title="View" href="{{ route($prefix . '.show', $row) }}"><i class="bi bi-eye"></i></a>
                        <a class="btn btn-sm btn-light" title="PDF invoice" target="_blank" rel="noopener" href="{{ route($prefix . '.pdf', $row) }}"><i class="bi bi-file-earmark-pdf"></i></a>
                        @if ($prefix === 'sales')
                            <a class="btn btn-sm btn-light" title="POS receipt" target="_blank" rel="noopener" href="{{ route('sales.receipt', $row) }}"><i class="bi bi-receipt-cutoff"></i></a>
                            <button type="button" class="btn btn-sm btn-light" title="Print POS receipt now" aria-label="Print POS receipt now" data-direct-print="{{ route('sales.receipt', $row) }}"><i class="bi bi-printer-fill"></i></button>
                        @endif
                    </td>
                </tr>
            @empty
                <x-empty-row :cols="9" />
            @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :items="$rows" />
</div>
