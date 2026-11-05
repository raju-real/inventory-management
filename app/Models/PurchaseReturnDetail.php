<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueId;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturnDetail extends Model
{
    use HasUniqueId;

    protected $fillable = ['purchase_return_id', 'purchase_detail_id', 'product_id', 'quantity', 'unit_price', 'total'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseDetail()
    {
        return $this->belongsTo(PurchaseDetail::class);
    }
}
