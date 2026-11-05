<?php

namespace App\Models;

use App\Models\Concerns\HasModelCache;
use App\Models\Concerns\HasUniqueId;
use Illuminate\Database\Eloquent\Model;

class IncomeCategory extends Model
{
    use HasModelCache, HasUniqueId;

    protected static array $cacheDependents = ['incomes'];

    protected $fillable = ['name', 'description', 'status'];

    protected $casts = ['status' => 'boolean'];

    public function entries()
    {
        return $this->hasMany(Income::class, 'category_id');
    }
}
