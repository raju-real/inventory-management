<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueId;
use App\Models\Concerns\HasModelCache;
use App\Models\Concerns\PostedDocument;
use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    use HasModelCache, PostedDocument, HasUniqueId;

    public const REF_PREFIX = 'PAY';

    protected static array $cacheDependents = ['suppliers'];

    protected $fillable = [
        'supplier_id', 'purchase_id', 'payment_date', 'amount', 'method', 'transaction_ref', 'notes',
        'status', 'cancel_reason', 'cancelled_by', 'cancelled_at',
    ];

    protected $casts = ['payment_date' => 'date', 'cancelled_at' => 'datetime'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}
