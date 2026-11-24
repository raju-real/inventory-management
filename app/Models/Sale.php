<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueId;
use App\Models\Concerns\HasInvoiceBalance;
use App\Models\Concerns\HasModelCache;
use App\Models\Concerns\PostedDocument;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasModelCache, PostedDocument, HasInvoiceBalance, HasUniqueId;

    public const REF_PREFIX = 'SAL';

    protected static array $cacheDependents = ['customers'];

    protected $fillable = [
        'sale_date', 'customer_id', 'total_quantity', 'subtotal', 'discount_type', 'discount_value', 'discount',
        'vat_mode', 'vat_type', 'vat_value', 'vat_amount', 'points_redeemed', 'points_amount', 'points_earned', 'points_reversed', 'grand_total',
        'paid_amount', 'returned_amount', 'due_amount', 'notes', 'status', 'cancel_reason', 'cancelled_by', 'cancelled_at',
    ];

    protected $casts = ['sale_date' => 'date', 'cancelled_at' => 'datetime', 'points_redeemed' => 'integer', 'points_earned' => 'integer', 'points_reversed' => 'integer'];

    public function getDiscountLabelAttribute(): string
    {
        return $this->discount_type === 'percentage' && (float) $this->discount_value > 0 ? 'Discount (' . qty($this->discount_value) . '%)' : 'Discount';
    }

    public function getVatLabelAttribute(): string
    {
        if ($this->vat_mode === 'order') {
            return $this->vat_type === 'percentage' ? 'VAT (' . qty($this->vat_value) . '% on total)' : 'VAT (on total)';
        }

        return 'VAT (product wise)';
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function details()
    {
        return $this->hasMany(SaleDetail::class);
    }

    public function payments()
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public function returns()
    {
        return $this->hasMany(SalesReturn::class);
    }
}
