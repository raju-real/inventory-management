<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueId;
use App\Models\Concerns\HasImage;
use App\Models\Concerns\HasModelCache;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasModelCache, HasImage, HasUniqueId;

    protected $fillable = [
        'name', 'sku', 'barcode', 'category_id', 'unit_id', 'purchase_price', 'sales_price', 'vat_type', 'vat_value',
        'min_stock', 'opening_stock', 'current_stock', 'description', 'image', 'status', 'created_by',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'sales_price' => 'decimal:2',
        'vat_value' => 'decimal:2',
        'min_stock' => 'decimal:3',
        'opening_stock' => 'decimal:3',
        'current_stock' => 'decimal:3',
        'status' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class);
    }

    public function scopeActive($query)
    {
        return $query->where('products.status', true);
    }

    public function scopeLowStock($query)
    {
        return $query->active()->where('min_stock', '>', 0)->whereColumn('current_stock', '<=', 'min_stock');
    }

    public function getIsLowStockAttribute(): bool
    {
        return (float) $this->min_stock > 0 && (float) $this->current_stock <= (float) $this->min_stock;
    }

    public function getVatLabelAttribute(): string
    {
        if ((float) $this->vat_value <= 0) {
            return 'No VAT';
        }

        return $this->vat_type === 'flat' ? money($this->vat_value) . ' per unit' : qty($this->vat_value) . '%';
    }

    /** The code printed on labels: the barcode when the product has one, otherwise the SKU. */
    public function getLabelCodeAttribute(): string
    {
        return $this->barcode ?: $this->sku;
    }
}
