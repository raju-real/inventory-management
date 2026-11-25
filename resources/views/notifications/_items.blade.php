{{-- Bell menu items. Needs: $items (AppNotification with read_at for the current user) --}}
@forelse ($items as $item)
    <div class="notify-item {{ $item->is_read ? 'is-read' : 'is-unread' }}" data-notification="{{ $item->unique_id }}">
        <a class="notify-link" href="{{ route('notifications.open', $item) }}">
            <span class="notify-icon text-{{ $item->tone }}"><i class="bi {{ $item->icon }}"></i></span>
            <span class="notify-body">
                <span class="notify-title">{{ $item->title }}</span>
                <span class="notify-text">{{ $item->message }}</span>
                <span class="notify-time">
                    {{ $item->created_at->diffForHumans() }}
                    @if ($item->resolved_at)<span class="badge text-bg-light border ms-1">Restocked</span>@endif
                </span>
            </span>
        </a>
        <button type="button" class="btn btn-sm notify-toggle" data-toggle-url="{{ route('notifications.toggle', $item) }}"
                title="{{ $item->is_read ? 'Mark as unread' : 'Mark as read' }}" aria-label="{{ $item->is_read ? 'Mark as unread' : 'Mark as read' }}">
            <span class="notify-dot"></span>
        </button>
    </div>
@empty
    <div class="notify-empty"><i class="bi bi-bell-slash d-block fs-3 mb-1"></i>No notifications yet.</div>
@endforelse
