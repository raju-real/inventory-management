<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\AppNotificationRead;
use App\Models\Product;
use App\Models\User;

/**
 * Stock alerts under the bell.
 *
 * A product's stock level is ok, low (at or below its minimum) or out (nothing left). When the
 * level changes, earlier alerts for the product are closed, and a new one is raised if the
 * product is now low or out. So each drop gives one alert, not one per sale.
 */
class NotificationService extends BaseService
{
    /** Users who look after stock see the alerts. */
    public static function canSee(?User $user): bool
    {
        return $user !== null && ($user->canAccess('products') || $user->canAccess('report-low-stock'));
    }

    public static function level(float $stock, float $minimum): string
    {
        if (round($stock, 3) <= 0) {
            return 'out';
        }

        return $minimum > 0 && round($stock, 3) <= round($minimum, 3) ? 'low' : 'ok';
    }

    /** Call after the product's stock or minimum changed, inside the same transaction. */
    public function stockChanged(Product $product, float $oldStock, float $oldMinimum): void
    {
        $before = self::level($oldStock, $oldMinimum);
        $after = self::level((float) $product->current_stock, (float) $product->min_stock);
        if ($before === $after) {
            return;
        }

        AppNotification::where('product_id', $product->id)->whereNull('resolved_at')->update(['resolved_at' => now()]);

        if ($after !== 'ok' && $product->status) {
            $this->raise($product, $after);
        }
    }

    /** One unseen alert for every product that is low or out of stock right now (first install). */
    public function alertCurrentLowStock(): void
    {
        Product::lowStock()->with('unit')->orderBy('name')->each(function (Product $product) {
            if (! AppNotification::where('product_id', $product->id)->whereNull('resolved_at')->exists()) {
                $this->raise($product, self::level((float) $product->current_stock, (float) $product->min_stock));
            }
        });
    }

    private function raise(Product $product, string $level): AppNotification
    {
        $unit = $product->unit?->short_name;
        $left = trim(qty($product->current_stock) . ' ' . $unit);

        return AppNotification::create($level === 'out' ? [
            'type' => 'out_of_stock',
            'product_id' => $product->id,
            'title' => 'Out of stock: ' . $product->name,
            'message' => 'No stock left. Purchase more to keep selling it.',
        ] : [
            'type' => 'low_stock',
            'product_id' => $product->id,
            'title' => 'Low stock: ' . $product->name,
            'message' => "Only {$left} left (minimum " . trim(qty($product->min_stock) . ' ' . $unit) . ').',
        ]);
    }

    public function unreadCount(User $user): int
    {
        return self::canSee($user) ? AppNotification::unreadBy($user)->count() : 0;
    }

    public function latest(User $user, int $limit = 8)
    {
        return AppNotification::forUser($user)->with('product:id,unique_id,name')
            ->orderByRaw('r.read_at IS NOT NULL')->latest('app_notifications.id')->limit($limit)->get();
    }

    public function paginate(User $user, array $filters)
    {
        $query = AppNotification::forUser($user)->with('product:id,unique_id,name')->latest('app_notifications.id');
        if (($filters['show'] ?? '') === 'unread') {
            $query->whereNull('r.read_at');
        }

        return $query->paginate(static::PER_PAGE)->withQueryString();
    }

    public function markRead(AppNotification $notification, User $user): void
    {
        AppNotificationRead::firstOrCreate(
            ['app_notification_id' => $notification->id, 'user_id' => $user->id],
            ['read_at' => now()]
        );
    }

    public function markUnread(AppNotification $notification, User $user): void
    {
        AppNotificationRead::where('app_notification_id', $notification->id)->where('user_id', $user->id)->delete();
    }

    public function isRead(AppNotification $notification, User $user): bool
    {
        return AppNotificationRead::where('app_notification_id', $notification->id)->where('user_id', $user->id)->exists();
    }

    public function markAllRead(User $user): int
    {
        $count = 0;
        AppNotification::unreadBy($user)->select('id')->chunkById(500, function ($chunk) use ($user, &$count) {
            AppNotificationRead::insertOrIgnore($chunk->map(fn ($notification) => [
                'unique_id' => AppNotificationRead::generateUniqueId(),
                'app_notification_id' => $notification->id,
                'user_id' => $user->id,
                'read_at' => now(),
            ])->all());
            $count += $chunk->count();
        });

        return $count;
    }

    /** Where an alert leads: the product, or the low stock report for users who cannot open products. */
    public function targetUrl(AppNotification $notification, User $user): string
    {
        if ($notification->product && $user->canAccess('products')) {
            return route('products.show', $notification->product);
        }

        return $user->canAccess('report-low-stock') ? route('reports.low-stock') : route('products.index', ['low_stock' => 1]);
    }
}
