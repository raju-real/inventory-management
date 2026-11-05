<?php

namespace App\Models;

use App\Models\Concerns\HasModelCache;
use App\Models\Concerns\HasUniqueId;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasModelCache, HasUniqueId;

    protected $fillable = ['bank_name', 'branch', 'account_number', 'opening_balance', 'current_balance', 'status', 'created_by'];

    protected $casts = ['opening_balance' => 'decimal:2', 'current_balance' => 'decimal:2', 'status' => 'boolean'];

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function incomes()
    {
        return $this->hasMany(Income::class);
    }

    public function getLabelAttribute(): string
    {
        return $this->bank_name . ' - ' . $this->account_number;
    }
}
