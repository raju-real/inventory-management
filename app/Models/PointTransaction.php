<?php

namespace App\Models;

use App\Models\Concerns\HasModelCache;
use App\Models\Concerns\HasUniqueId;
use Illuminate\Database\Eloquent\Model;

class PointTransaction extends Model
{
    use HasModelCache, HasUniqueId;

    public const TYPES = [
        'earned' => 'Earned',
        'redeemed' => 'Used on a sale',
        'returned' => 'Taken back for a return',
        'cancelled' => 'Sale cancelled',
    ];

    protected static array $cacheDependents = ['customers'];

    protected $fillable = ['customer_id', 'sale_id', 'sales_return_id', 'type', 'points', 'amount', 'remarks', 'created_by'];

    protected $casts = ['amount' => 'decimal:2'];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function salesReturn()
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
