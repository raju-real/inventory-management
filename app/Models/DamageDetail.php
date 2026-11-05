<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueId;
use Illuminate\Database\Eloquent\Model;

class DamageDetail extends Model
{
    use HasUniqueId;

    protected $fillable = ['damage_id', 'product_id', 'quantity', 'unit_cost', 'reason'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function damage()
    {
        return $this->belongsTo(Damage::class);
    }
}
