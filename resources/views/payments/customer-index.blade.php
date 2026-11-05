@extends('layouts.app')
@section('title', 'Customer Payments')
@section('content')
<x-page-header title="Customer Payments">
    @access('customer-payments', 'create')<a class="btn btn-primary btn-submit" href="{{ route('customer-payments.create') }}"><i class="bi bi-plus-lg me-1"></i>Receive Payment</a>@endaccess
</x-page-header>
<x-filter search="Search by reference or customer">
    <x-filter-select name="status" label="Status" :options="['posted' => 'Posted', 'cancelled' => 'Cancelled']" />
    <x-filter-dates />
</x-filter>
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Reference</th><th>Date</th><th>Customer</th><th>Invoice</th><th>Method</th><th class="text-end">Amount</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($payments as $payment)
                <tr>
                    <td class="fw-medium">{{ $payment->reference_no }}</td>
                    <td>{{ $payment->payment_date->format('d M Y') }}</td>
                    <td>{{ $payment->customer?->name }}</td>
                    <td>{{ $payment->sale?->reference_no ?? 'On account' }}</td>
                    <td class="text-capitalize">{{ $payment->method }}</td>
                    <td class="num">{{ number_format($payment->amount, 2) }}</td>
                    <td><x-doc-status :status="$payment->status" /></td>
                    <td class="table-actions">
                        @unless ($payment->isCancelled())
                            @access('customer-payments', 'delete')<button class="btn btn-sm btn-light text-danger" title="Cancel" data-cancel-url="{{ route('customer-payments.cancel', $payment) }}" data-name="{{ $payment->reference_no }}"><i class="bi bi-x-circle"></i></button>@endaccess
                        @endunless
                    </td>
                </tr>
            @empty
                <x-empty-row :cols="8" />
            @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :items="$payments" />
</div>
@endsection
