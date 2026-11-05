<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueId;
use App\Models\Concerns\HasInvoiceBalance;
use App\Models\Concerns\HasModelCache;
use App\Models\Concerns\PostedDocument;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasModelCache, PostedDocument, HasInvoiceBalance, HasUniqueId;

    public const REF_PREFIX = 'PUR';

    protected static array $cacheDependents = ['suppliers'];

    protected $fillable = [
        'purchase_date', 'supplier_id', 'total_quantity', 'subtotal', 'discount', 'other_charges',
        'grand_total', 'paid_amount', 'returned_amount', 'due_amount', 'notes', 'status', 'cancel_reason', 'cancelled_by', 'cancelled_at',
    ];

    protected $casts = ['purchase_date' => 'date', 'cancelled_at' => 'datetime'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function details()
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function returns()
    {
        return $this->hasMany(PurchaseReturn::class);
    }
}
