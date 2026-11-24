<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueId;
use App\Models\Concerns\HasImage;
use App\Models\Concerns\HasModelCache;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasModelCache, HasImage, HasUniqueId;

    protected $fillable = ['name', 'mobile', 'email', 'address', 'opening_balance', 'image', 'status'];

    protected $casts = ['opening_balance' => 'decimal:2', 'status' => 'boolean', 'is_walk_in' => 'boolean', 'points' => 'integer'];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function payments()
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public function salesReturns()
    {
        return $this->hasMany(SalesReturn::class);
    }

    public function pointTransactions()
    {
        return $this->hasMany(PointTransaction::class);
    }

    public static function cachedActive()
    {
        return static::rememberCache('active', fn () => static::where('status', true)->orderBy('name')->get(['id', 'unique_id', 'name', 'mobile', 'email', 'is_walk_in', 'points']));
    }

    /** Adds the sums needed by the balance accessor. */
    public function scopeWithBalance($query)
    {
        $posted = fn ($q) => $q->where('status', 'posted');

        return $query->withSum(['sales as sales_total' => $posted], 'grand_total')
            ->withSum(['payments as payments_total' => $posted], 'amount')
            ->withSum(['salesReturns as returns_total' => $posted], 'total_amount')
            ->withSum(['salesReturns as refunds_total' => $posted], 'refund_amount')
            ->withSum(['sales as points_paid_total' => $posted], 'points_amount');
    }

    /** Amount the customer owes us; negative means the customer has credit. */
    public function getBalanceAttribute(): float
    {
        if (! array_key_exists('sales_total', $this->attributes)) {
            $this->loadSum(['sales as sales_total' => fn ($q) => $q->where('status', 'posted')], 'grand_total');
            $this->loadSum(['payments as payments_total' => fn ($q) => $q->where('status', 'posted')], 'amount');
            $this->loadSum(['salesReturns as returns_total' => fn ($q) => $q->where('status', 'posted')], 'total_amount');
            $this->loadSum(['salesReturns as refunds_total' => fn ($q) => $q->where('status', 'posted')], 'refund_amount');
            $this->loadSum(['sales as points_paid_total' => fn ($q) => $q->where('status', 'posted')], 'points_amount');
        }

        return round((float) $this->opening_balance + (float) $this->sales_total - (float) $this->payments_total
            - (float) $this->returns_total + (float) $this->refunds_total - (float) $this->points_paid_total, 2);
    }
}
