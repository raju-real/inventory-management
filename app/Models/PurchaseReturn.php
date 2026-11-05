<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueId;
use App\Models\Concerns\HasModelCache;
use App\Models\Concerns\PostedDocument;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    use HasModelCache, PostedDocument, HasUniqueId;

    public const REF_PREFIX = 'PRT';

    protected static array $cacheDependents = ['suppliers'];

    protected $fillable = [
        'purchase_id', 'supplier_id', 'return_date', 'total_quantity', 'total_amount', 'refund_amount', 'reason', 'notes',
        'status', 'cancel_reason', 'cancelled_by', 'cancelled_at',
    ];

    protected $casts = ['return_date' => 'date', 'cancelled_at' => 'datetime'];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function details()
    {
        return $this->hasMany(PurchaseReturnDetail::class);
    }
}
