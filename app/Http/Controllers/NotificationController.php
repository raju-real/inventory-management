<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $service)
    {
        $this->middleware(function ($request, $next) {
            abort_unless(NotificationService::canSee($request->user()), 403);

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $user = $request->user();

        return view('notifications.index', [
            'notifications' => $this->service->paginate($user, $request->only('show')),
            'unread' => $this->service->unreadCount($user),
            'show' => $request->query('show') === 'unread' ? 'unread' : 'all',
        ]);
    }

    /** The bell: unseen count, plus the latest alerts when the menu opens. */
    public function feed(Request $request)
    {
        $user = $request->user();
        $data = ['count' => $this->service->unreadCount($user)];
        if (! $request->boolean('count_only')) {
            $data['html'] = view('notifications._items', ['items' => $this->service->latest($user)])->render();
        }

        return response()->json($data);
    }

    /** Opening an alert marks it seen and goes to the product. */
    public function open(Request $request, AppNotification $notification)
    {
        $this->service->markRead($notification, $request->user());

        return redirect()->to($this->service->targetUrl($notification, $request->user()));
    }

    public function toggle(Request $request, AppNotification $notification)
    {
        $user = $request->user();
        $read = ! $this->service->isRead($notification, $user);
        $read ? $this->service->markRead($notification, $user) : $this->service->markUnread($notification, $user);

        if ($request->expectsJson()) {
            return response()->json(['read' => $read, 'count' => $this->service->unreadCount($user)]);
        }

        return back()->with('success', $read ? 'Marked as read.' : 'Marked as unread.');
    }

    public function readAll(Request $request)
    {
        $marked = $this->service->markAllRead($request->user());

        if ($request->expectsJson()) {
            return response()->json(['count' => 0, 'marked' => $marked]);
        }

        return back()->with('success', $marked ? 'All notifications marked as read.' : 'Nothing unread.');
    }
}
