@extends('layouts.app')
@section('title', $title)
@section('content')
<x-page-header :title="$title">
    @access($menuKey, 'create')<a class="btn btn-primary btn-submit" href="{{ route($prefix . '.create') }}"><i class="bi bi-plus-lg me-1"></i>Add {{ $word }}</a>@endaccess
</x-page-header>
<x-filter search="Search by category name">
    <x-filter-select name="status" label="Status" :options="[1 => 'Active', 0 => 'Inactive']" />
</x-filter>
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Name</th><th>Description</th><th class="text-end">Entries</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($categories as $category)
                <tr>
                    <td class="fw-medium">{{ $category->name }}</td>
                    <td>{{ $category->description }}</td>
                    <td class="num">{{ $category->entries_count }}</td>
                    <td><x-status :active="$category->status" /></td>
                    <td class="table-actions">
                        @access($menuKey, 'edit')<a class="btn btn-sm btn-light" title="Edit" href="{{ route($prefix . '.edit', $category) }}"><i class="bi bi-pencil"></i></a>@endaccess
                        @access($menuKey, 'delete')<button class="btn btn-sm btn-light text-danger" title="Delete" data-delete-url="{{ route($prefix . '.destroy', $category) }}" data-name="{{ $category->name }}"><i class="bi bi-trash"></i></button>@endaccess
                    </td>
                </tr>
            @empty
                <x-empty-row :cols="5" />
            @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :items="$categories" />
</div>
@endsection
