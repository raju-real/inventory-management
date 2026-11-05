@extends('layouts.app')
@section('title', 'Units')
@section('content')
<x-page-header title="Units">
    @access('units', 'create')<a class="btn btn-primary btn-submit" href="{{ route('units.create') }}"><i class="bi bi-plus-lg me-1"></i>Add Unit</a>@endaccess
</x-page-header>
<x-filter search="Search by unit name">
    <x-filter-select name="status" label="Status" :options="[1 => 'Active', 0 => 'Inactive']" />
</x-filter>
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Name</th><th>Short name</th><th class="text-end">Products</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($units as $unit)
                <tr>
                    <td class="fw-medium">{{ $unit->name }}</td>
                    <td>{{ $unit->short_name }}</td>
                    <td class="num">{{ $unit->products_count }}</td>
                    <td><x-status :active="$unit->status" /></td>
                    <td class="table-actions">
                        @access('units', 'edit')<a class="btn btn-sm btn-light" title="Edit" href="{{ route('units.edit', $unit) }}"><i class="bi bi-pencil"></i></a>@endaccess
                        @access('units', 'delete')<button class="btn btn-sm btn-light text-danger" title="Delete" data-delete-url="{{ route('units.destroy', $unit) }}" data-name="{{ $unit->name }}"><i class="bi bi-trash"></i></button>@endaccess
                    </td>
                </tr>
            @empty
                <x-empty-row :cols="5" />
            @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :items="$units" />
</div>
@endsection
