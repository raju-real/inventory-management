<?php

namespace App\Models;

use App\Models\Concerns\HasModelCache;
use App\Models\Concerns\HasUniqueId;
use Illuminate\Database\Eloquent\Model;

class Income extends Model
{
    use HasModelCache, HasUniqueId;

    protected static array $cacheDependents = ['bank_accounts'];

    protected $fillable = ['entry_date', 'category_id', 'bank_account_id', 'amount', 'reference', 'notes', 'created_by'];

    protected $casts = ['entry_date' => 'date', 'amount' => 'decimal:2'];

    public function category()
    {
        return $this->belongsTo(IncomeCategory::class, 'category_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
