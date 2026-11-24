<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueId;
use Illuminate\Database\Eloquent\Model;

class SaleDetail extends Model
{
    use HasUniqueId;

    protected $fillable = ['sale_id', 'product_id', 'quantity', 'returned_quantity', 'unit_price', 'total', 'vat_type', 'vat_value', 'vat_amount'];

    /** VAT charged on one unit of this line. */
    public function getVatPerUnitAttribute(): float
    {
        return (float) $this->quantity > 0 ? (float) $this->vat_amount / (float) $this->quantity : 0;
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function getReturnableQuantityAttribute(): float
    {
        return round((float) $this->quantity - (float) $this->returned_quantity, 3);
    }
}
