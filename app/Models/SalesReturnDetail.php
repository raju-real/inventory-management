<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueId;
use Illuminate\Database\Eloquent\Model;

class SalesReturnDetail extends Model
{
    use HasUniqueId;

    protected $fillable = ['sales_return_id', 'sale_detail_id', 'product_id', 'quantity', 'unit_price', 'total', 'condition'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function saleDetail()
    {
        return $this->belongsTo(SaleDetail::class);
    }
}
