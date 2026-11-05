<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Shared behaviour of posted transaction documents: automatic reference number,
 * recorded-by user and cancellation (documents are cancelled, never deleted).
 */
trait PostedDocument
{
    public static function bootPostedDocument(): void
    {
        static::creating(function ($model) {
            $model->reference_no = 'TMP-' . Str::uuid();
            $model->created_by = $model->created_by ?? auth()->id();
            $model->status = $model->status ?? 'posted';
        });

        static::created(function ($model) {
            $model->reference_no = static::REF_PREFIX . '-' . str_pad($model->id, 6, '0', STR_PAD_LEFT);
            $model->saveQuietly();
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function scopePosted($query)
    {
        return $query->where($this->getTable() . '.status', 'posted');
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function markCancelled(?string $reason): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancel_reason' => $reason,
            'cancelled_by' => auth()->id(),
            'cancelled_at' => now(),
        ]);
    }
}
