<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueId;
use App\Models\Concerns\HasModelCache;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasModelCache, HasUniqueId;

    protected static string $cacheGroupName = 'access';

    public const ACTION_LABELS = ['view' => 'View', 'create' => 'Create', 'edit' => 'Edit', 'delete' => 'Delete / Cancel'];

    protected $fillable = ['parent_id', 'name', 'key', 'route', 'icon', 'actions', 'admin_only', 'is_public', 'sort_order', 'is_active'];

    protected $casts = [
        'actions' => 'array',
        'admin_only' => 'boolean',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public static function cachedTree()
    {
        return static::rememberCache('tree', fn () => static::with('children')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get());
    }
}
