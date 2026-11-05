<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueId;
use App\Models\Concerns\HasModelCache;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasModelCache, HasUniqueId;

    protected static array $cacheDependents = ['products'];

    protected $fillable = ['name', 'description', 'status'];

    protected $casts = ['status' => 'boolean'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public static function cachedActive()
    {
        return static::rememberCache('active', fn () => static::where('status', true)->orderBy('name')->get());
    }
}
