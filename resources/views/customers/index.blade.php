@extends('layouts.app')
@section('title', 'Customers')
@section('content')
<x-page-header title="Customers">
    @access('customers', 'create')<a class="btn btn-primary btn-submit" href="{{ route('customers.create') }}"><i class="bi bi-plus-lg me-1"></i>Add Customer</a>@endaccess
</x-page-header>
<x-filter search="Search by name, mobile or email">
    <x-filter-select name="status" label="Status" :options="[1 => 'Active', 0 => 'Inactive']" />
</x-filter>
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Customer</th><th>Mobile</th><th>Email</th><th class="text-end">Outstanding</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($customers as $customer)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            @if ($customer->image_url)<img class="thumb" src="{{ $customer->image_url }}" alt="">@else<span class="thumb"><i class="bi bi-person"></i></span>@endif
                            <a class="fw-medium text-decoration-none" href="{{ route('customers.show', $customer) }}">{{ $customer->name }}</a>
                        </div>
                    </td>
                    <td>{{ $customer->mobile }}</td>
                    <td>{{ $customer->email }}</td>
                    <td class="num {{ $customer->balance > 0 ? 'out' : '' }}">{{ number_format($customer->balance, 2) }}</td>
                    <td><x-status :active="$customer->status" /></td>
                    <td class="table-actions">
                        @access('customers', 'edit')<a class="btn btn-sm btn-light" title="Edit" href="{{ route('customers.edit', $customer) }}"><i class="bi bi-pencil"></i></a>@endaccess
                        @access('customers', 'delete')<button class="btn btn-sm btn-light text-danger" title="Delete" data-delete-url="{{ route('customers.destroy', $customer) }}" data-name="{{ $customer->name }}"><i class="bi bi-trash"></i></button>@endaccess
                    </td>
                </tr>
            @empty
                <x-empty-row :cols="6" />
            @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :items="$customers" />
</div>
@endsection
