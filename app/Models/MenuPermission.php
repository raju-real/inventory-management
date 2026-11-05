<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueId;
use App\Models\Concerns\HasModelCache;
use Illuminate\Database\Eloquent\Model;

class MenuPermission extends Model
{
    use HasModelCache, HasUniqueId;

    protected static string $cacheGroupName = 'access';

    protected $fillable = ['user_id', 'menu_id', 'actions'];

    protected $casts = ['actions' => 'array'];

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}
