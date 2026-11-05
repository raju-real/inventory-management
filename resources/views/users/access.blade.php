@extends('layouts.app')
@section('title', 'Manage Access')
@section('content')
<x-page-header title="Manage Access" :sub="$user->name . ' · ' . $user->email">
    <a class="btn btn-light" href="{{ route('users.index') }}">Back</a>
    <button class="btn btn-outline-primary" type="button" id="select-all">Select all</button>
    <button class="btn btn-light" type="button" id="clear-all">Clear all</button>
</x-page-header>
@if ($user->isAdmin())
    <div class="alert alert-info">This user is an admin and can open every menu. These choices only apply if the role changes to User.</div>
@endif
<form method="POST" action="{{ route('users.access.update', $user) }}" id="access-form">
    @csrf @method('PUT')
    @foreach ($tree as $menu)
        <div class="access-group">
            @if ($menu->children->isNotEmpty())
                <div class="access-group-head">
                    <input class="form-check-input m-0" type="checkbox" data-group id="group-{{ $menu->unique_id }}">
                    <label for="group-{{ $menu->unique_id }}"><i class="bi {{ $menu->icon }} me-1"></i>{{ $menu->name }}</label>
                </div>
            @endif
            @foreach ($menu->children->isNotEmpty() ? $menu->children : [$menu] as $item)
                <div class="access-row" data-row>
                    <div class="name">
                        <input class="form-check-input m-0" type="checkbox" data-menu id="menu-{{ $item->unique_id }}" @checked(isset($granted[$item->unique_id]))>
                        <label for="menu-{{ $item->unique_id }}"><i class="bi {{ $item->icon }} me-1"></i>{{ $item->name }}</label>
                    </div>
                    @foreach ($item->actions as $action)
                        <div class="form-check form-check-inline m-0">
                            <input class="form-check-input" type="checkbox" name="permissions[{{ $item->unique_id }}][]" value="{{ $action }}" id="p-{{ $item->unique_id }}-{{ $action }}" data-action="{{ $action }}"
                                   @checked(in_array($action, $granted[$item->unique_id] ?? [], true))>
                            <label class="form-check-label small" for="p-{{ $item->unique_id }}-{{ $action }}">{{ \App\Models\Menu::ACTION_LABELS[$action] }}</label>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endforeach
    <div class="form-actions"><button class="btn btn-primary btn-submit" type="submit"><i class="bi bi-check2-circle me-1"></i>Save Access</button></div>
</form>
@endsection
@push('scripts')
<script>
    (function () {
        const form = document.getElementById('access-form');

        function syncGroups() {
            form.querySelectorAll('.access-group').forEach(function (group) {
                const head = group.querySelector('[data-group]');
                if (!head) {
                    return;
                }
                const menus = group.querySelectorAll('[data-menu]');
                const on = group.querySelectorAll('[data-menu]:checked').length;
                head.checked = on === menus.length;
                head.indeterminate = on > 0 && on < menus.length;
            });
        }

        function setRow(row, checked) {
            row.querySelector('[data-menu]').checked = checked;
            row.querySelectorAll('[data-action]').forEach(function (box) { box.checked = checked; });
        }

        form.addEventListener('change', function (event) {
            const box = event.target;
            const row = box.closest('[data-row]');

            if (box.dataset.group !== undefined) {
                box.closest('.access-group').querySelectorAll('[data-row]').forEach(function (r) { setRow(r, box.checked); });
            } else if (box.dataset.menu !== undefined) {
                // Ticking a menu gives view access; unticking it removes everything on the row.
                row.querySelectorAll('[data-action]').forEach(function (action) {
                    if (!box.checked) {
                        action.checked = false;
                    } else if (action.dataset.action === 'view') {
                        action.checked = true;
                    }
                });
            } else if (box.dataset.action !== undefined) {
                const view = row.querySelector('[data-action="view"]');
                if (box.checked) {
                    view.checked = true;
                } else if (box === view) {
                    row.querySelectorAll('[data-action]').forEach(function (action) { action.checked = false; });
                }
                row.querySelector('[data-menu]').checked = view.checked;
            }
            syncGroups();
        });

        document.getElementById('select-all').addEventListener('click', function () {
            form.querySelectorAll('[data-row]').forEach(function (row) { setRow(row, true); });
            syncGroups();
        });
        document.getElementById('clear-all').addEventListener('click', function () {
            form.querySelectorAll('[data-row]').forEach(function (row) { setRow(row, false); });
            syncGroups();
        });

        syncGroups();
    })();
</script>
@endpush
