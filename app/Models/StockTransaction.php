<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueId;
use App\Models\Concerns\HasModelCache;
use Illuminate\Database\Eloquent\Model;

class StockTransaction extends Model
{
    use HasModelCache, HasUniqueId;

    public const TYPES = [
        'opening' => 'Opening Stock',
        'purchase' => 'Purchase',
        'purchase_cancel' => 'Purchase Cancelled',
        'sale' => 'Sale',
        'sale_cancel' => 'Sale Cancelled',
        'sales_return' => 'Sales Return (Good)',
        'sales_return_damaged' => 'Sales Return (Damaged)',
        'sales_return_cancel' => 'Sales Return Cancelled',
        'purchase_return' => 'Purchase Return',
        'purchase_return_cancel' => 'Purchase Return Cancelled',
        'damage' => 'Damage',
        'damage_cancel' => 'Damage Cancelled',
        'adjustment' => 'Stock Adjustment',
        'adjustment_cancel' => 'Adjustment Cancelled',
    ];

    protected $fillable = [
        'product_id', 'transaction_date', 'transaction_type', 'reference_type', 'reference_id', 'reference_no',
        'quantity_in', 'quantity_out', 'unit_cost', 'remarks', 'created_by',
    ];

    protected $casts = ['transaction_date' => 'date'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->transaction_type] ?? $this->transaction_type;
    }
}
