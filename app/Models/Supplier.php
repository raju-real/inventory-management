<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueId;
use App\Models\Concerns\HasImage;
use App\Models\Concerns\HasModelCache;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasModelCache, HasImage, HasUniqueId;

    protected $fillable = ['name', 'contact_person', 'mobile', 'email', 'address', 'opening_balance', 'image', 'status'];

    protected $casts = ['opening_balance' => 'decimal:2', 'status' => 'boolean'];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function purchaseReturns()
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public static function cachedActive()
    {
        return static::rememberCache('active', fn () => static::where('status', true)->orderBy('name')->get(['id', 'unique_id', 'name', 'mobile', 'email']));
    }

    public function scopeWithBalance($query)
    {
        $posted = fn ($q) => $q->where('status', 'posted');

        return $query->withSum(['purchases as purchases_total' => $posted], 'grand_total')
            ->withSum(['payments as payments_total' => $posted], 'amount')
            ->withSum(['purchaseReturns as returns_total' => $posted], 'total_amount')
            ->withSum(['purchaseReturns as refunds_total' => $posted], 'refund_amount');
    }

    /** Amount we owe the supplier; negative means the supplier owes us. */
    public function getBalanceAttribute(): float
    {
        if (! array_key_exists('purchases_total', $this->attributes)) {
            $this->loadSum(['purchases as purchases_total' => fn ($q) => $q->where('status', 'posted')], 'grand_total');
            $this->loadSum(['payments as payments_total' => fn ($q) => $q->where('status', 'posted')], 'amount');
            $this->loadSum(['purchaseReturns as returns_total' => fn ($q) => $q->where('status', 'posted')], 'total_amount');
            $this->loadSum(['purchaseReturns as refunds_total' => fn ($q) => $q->where('status', 'posted')], 'refund_amount');
        }

        return round((float) $this->opening_balance + (float) $this->purchases_total - (float) $this->payments_total
            - (float) $this->returns_total + (float) $this->refunds_total, 2);
    }
}
