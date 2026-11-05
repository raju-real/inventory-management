<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueId;
use App\Models\Concerns\HasModelCache;
use App\Models\Concerns\PostedDocument;
use Illuminate\Database\Eloquent\Model;

class CustomerPayment extends Model
{
    use HasModelCache, PostedDocument, HasUniqueId;

    public const REF_PREFIX = 'RCV';

    protected static array $cacheDependents = ['customers'];

    protected $fillable = [
        'customer_id', 'sale_id', 'payment_date', 'amount', 'method', 'transaction_ref', 'notes',
        'status', 'cancel_reason', 'cancelled_by', 'cancelled_at',
    ];

    protected $casts = ['payment_date' => 'date', 'cancelled_at' => 'datetime'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
