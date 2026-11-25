<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueId;
use Illuminate\Database\Eloquent\Model;

/** A stock alert under the bell. Each user sees it as unseen until they open it or mark it read. */
class AppNotification extends Model
{
    use HasUniqueId;

    public const TYPES = [
        'low_stock' => ['Low stock', 'bi-exclamation-triangle-fill', 'warning'],
        'out_of_stock' => ['Out of stock', 'bi-x-octagon-fill', 'danger'],
    ];

    protected $fillable = ['type', 'product_id', 'title', 'message', 'resolved_at'];

    protected $casts = ['resolved_at' => 'datetime', 'read_at' => 'datetime'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function reads()
    {
        return $this->hasMany(AppNotificationRead::class);
    }

    /** Adds read_at for one user: null while that user has not seen it. */
    public function scopeForUser($query, User $user)
    {
        return $query->select('app_notifications.*', 'r.read_at')
            ->leftJoin('app_notification_reads as r', function ($join) use ($user) {
                $join->on('r.app_notification_id', '=', 'app_notifications.id')->where('r.user_id', '=', $user->id);
            });
    }

    public function scopeUnreadBy($query, User $user)
    {
        return $query->whereNotExists(function ($sub) use ($user) {
            $sub->selectRaw('1')->from('app_notification_reads')
                ->whereColumn('app_notification_reads.app_notification_id', 'app_notifications.id')
                ->where('app_notification_reads.user_id', $user->id);
        });
    }

    public function getIsReadAttribute(): bool
    {
        return $this->read_at !== null;
    }

    public function getIconAttribute(): string
    {
        return self::TYPES[$this->type][1] ?? 'bi-bell';
    }

    public function getToneAttribute(): string
    {
        return self::TYPES[$this->type][2] ?? 'secondary';
    }
}
