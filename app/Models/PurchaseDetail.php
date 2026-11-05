<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueId;
use Illuminate\Database\Eloquent\Model;

class PurchaseDetail extends Model
{
    use HasUniqueId;

    protected $fillable = ['purchase_id', 'product_id', 'quantity', 'returned_quantity', 'unit_price', 'total'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function getReturnableQuantityAttribute(): float
    {
        return round((float) $this->quantity - (float) $this->returned_quantity, 3);
    }
}
