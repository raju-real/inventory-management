<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueId;
use App\Models\Concerns\HasModelCache;
use App\Models\Concerns\PostedDocument;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use HasModelCache, PostedDocument, HasUniqueId;

    public const REF_PREFIX = 'ADJ';

    protected $fillable = ['adjustment_date', 'product_id', 'type', 'quantity', 'reason', 'status', 'cancel_reason', 'cancelled_by', 'cancelled_at'];

    protected $casts = ['adjustment_date' => 'date', 'cancelled_at' => 'datetime'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
