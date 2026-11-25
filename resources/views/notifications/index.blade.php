@extends('layouts.app')
@section('title', 'Notifications')
@section('content')
<x-page-header title="Notifications" sub="Stock alerts. Each alert stays unseen for you until you open it or mark it read.">
    @if ($unread > 0)
        <form method="POST" action="{{ route('notifications.read-all') }}">@csrf
            <button class="btn btn-outline-primary btn-submit" type="submit"><i class="bi bi-check2-all me-1"></i>Mark all as read</button>
        </form>
    @endif
</x-page-header>
<ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link {{ $show === 'all' ? 'active' : '' }}" href="{{ route('notifications.index') }}">All</a></li>
    <li class="nav-item"><a class="nav-link {{ $show === 'unread' ? 'active' : '' }}" href="{{ route('notifications.index', ['show' => 'unread']) }}">Unread <span class="badge rounded-pill {{ $show === 'unread' ? 'text-bg-light' : 'text-bg-danger' }} ms-1">{{ $unread }}</span></a></li>
</ul>
<div class="card">
    <div class="table-responsive">
        <table class="table notify-table">
            <thead><tr><th style="width:28px"></th><th>Alert</th><th>Date</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($notifications as $item)
                <tr class="{{ $item->is_read ? 'is-read' : 'is-unread' }}">
                    <td><span class="notify-icon text-{{ $item->tone }}"><i class="bi {{ $item->icon }}"></i></span></td>
                    <td>
                        <a class="notify-title text-decoration-none" href="{{ route('notifications.open', $item) }}">{{ $item->title }}</a>
                        @unless ($item->is_read)<span class="badge text-bg-primary ms-1">New</span>@endunless
                        <div class="small text-muted">{{ $item->message }}</div>
                    </td>
                    <td class="text-nowrap">{{ $item->created_at->format('d M Y, h:i A') }}<div class="small text-muted">{{ $item->created_at->diffForHumans() }}</div></td>
                    <td>
                        @if ($item->resolved_at)
                            <span class="badge text-bg-success">Restocked</span>
                        @else
                            <span class="badge text-bg-{{ $item->tone }}">{{ \App\Models\AppNotification::TYPES[$item->type][0] ?? 'Open' }}</span>
                        @endif
                    </td>
                    <td class="table-actions">
                        <form method="POST" action="{{ route('notifications.toggle', $item) }}" class="d-inline">@csrf
                            <button class="btn btn-sm btn-light btn-submit" type="submit" title="{{ $item->is_read ? 'Mark as unread' : 'Mark as read' }}" aria-label="{{ $item->is_read ? 'Mark as unread' : 'Mark as read' }}">
                                <i class="bi {{ $item->is_read ? 'bi-envelope' : 'bi-envelope-open' }}"></i>
                            </button>
                        </form>
                        <a class="btn btn-sm btn-light" href="{{ route('notifications.open', $item) }}" title="Open" aria-label="Open"><i class="bi bi-box-arrow-up-right"></i></a>
                    </td>
                </tr>
            @empty
                <x-empty-row :cols="5" :text="$show === 'unread' ? 'You have no unread notifications.' : 'No notifications yet.'" />
            @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :items="$notifications" />
</div>
@endsection
