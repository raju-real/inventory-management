<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueId;
use App\Models\Concerns\HasImage;
use App\Models\Concerns\HasModelCache;
use App\Support\VersionedCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasModelCache, HasImage, HasUniqueId;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';

    protected static array $cacheDependents = ['access'];

    protected $fillable = ['name', 'email', 'phone', 'role', 'image', 'status', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'status' => 'boolean',
    ];

    public function menuPermissions()
    {
        return $this->hasMany(MenuPermission::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** ['products' => ['view', 'create'], ...] for this user. */
    public function accessMap(): array
    {
        return VersionedCache::remember('access', "user:{$this->id}", function () {
            return MenuPermission::with('menu')
                ->where('user_id', $this->id)
                ->get()
                ->filter(fn ($permission) => $permission->menu && $permission->menu->is_active && ! $permission->menu->admin_only)
                ->mapWithKeys(fn ($permission) => [$permission->menu->key => $permission->actions])
                ->all();
        });
    }

    public function canAccess(string $menuKey, string $action = 'view'): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return in_array($action, $this->accessMap()[$menuKey] ?? [], true);
    }

    /** Where to land after login: first menu the user may open, profile as fallback. */
    public function homeRoute(): string
    {
        foreach (Menu::cachedTree() as $menu) {
            foreach ($menu->children->isEmpty() ? [$menu] : $menu->children as $item) {
                if ($item->route && $item->is_active && ! $item->is_public && $this->canAccess($item->key)) {
                    return $item->route;
                }
            }
        }

        return 'profile.edit';
    }
}
