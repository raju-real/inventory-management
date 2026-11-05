@extends('layouts.app')
@section('title', 'Suppliers')
@section('content')
<x-page-header title="Suppliers">
    @access('suppliers', 'create')<a class="btn btn-primary btn-submit" href="{{ route('suppliers.create') }}"><i class="bi bi-plus-lg me-1"></i>Add Supplier</a>@endaccess
</x-page-header>
<x-filter search="Search by name, contact, mobile or email">
    <x-filter-select name="status" label="Status" :options="[1 => 'Active', 0 => 'Inactive']" />
</x-filter>
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Supplier</th><th>Contact person</th><th>Mobile</th><th>Email</th><th class="text-end">Payable</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($suppliers as $supplier)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            @if ($supplier->image_url)<img class="thumb" src="{{ $supplier->image_url }}" alt="">@else<span class="thumb"><i class="bi bi-truck"></i></span>@endif
                            <a class="fw-medium text-decoration-none" href="{{ route('suppliers.show', $supplier) }}">{{ $supplier->name }}</a>
                        </div>
                    </td>
                    <td>{{ $supplier->contact_person }}</td>
                    <td>{{ $supplier->mobile }}</td>
                    <td>{{ $supplier->email }}</td>
                    <td class="num {{ $supplier->balance > 0 ? 'out' : '' }}">{{ number_format($supplier->balance, 2) }}</td>
                    <td><x-status :active="$supplier->status" /></td>
                    <td class="table-actions">
                        @access('suppliers', 'edit')<a class="btn btn-sm btn-light" title="Edit" href="{{ route('suppliers.edit', $supplier) }}"><i class="bi bi-pencil"></i></a>@endaccess
                        @access('suppliers', 'delete')<button class="btn btn-sm btn-light text-danger" title="Delete" data-delete-url="{{ route('suppliers.destroy', $supplier) }}" data-name="{{ $supplier->name }}"><i class="bi bi-trash"></i></button>@endaccess
                    </td>
                </tr>
            @empty
                <x-empty-row :cols="7" />
            @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :items="$suppliers" />
</div>
@endsection
