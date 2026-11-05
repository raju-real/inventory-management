<?php

namespace App\Models;

use App\Models\Concerns\HasModelCache;
use App\Models\Concerns\HasUniqueId;
use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    use HasModelCache, HasUniqueId;

    protected static array $cacheDependents = ['expenses'];

    protected $fillable = ['name', 'description', 'status'];

    protected $casts = ['status' => 'boolean'];

    public function entries()
    {
        return $this->hasMany(Expense::class, 'category_id');
    }
}
