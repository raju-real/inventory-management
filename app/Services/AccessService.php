<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\MenuPermission;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AccessService
{
    /** Menu tree the user can see in the sidebar. */
    public function sidebarFor(User $user): Collection
    {
        $visible = fn (Menu $menu) => $menu->is_active && ($menu->is_public || ($menu->route && $user->canAccess($menu->key)));

        return Menu::cachedTree()
            ->filter(fn (Menu $menu) => $menu->is_active)
            ->map(function (Menu $menu) use ($visible) {
                $item = clone $menu;
                $item->setRelation('children', $menu->children->filter($visible)->values());

                return $item;
            })
            ->filter(fn (Menu $menu) => $menu->children->isNotEmpty() || $visible($menu))
            ->values();
    }

    /** Menus the admin can assign: admin-only and public ones are not assignable. */
    public function assignableTree(): Collection
    {
        return Menu::cachedTree()
            ->map(function (Menu $menu) {
                $item = clone $menu;
                $item->setRelation('children', $menu->children->filter(fn ($child) => $this->assignable($child))->values());

                return $item;
            })
            ->filter(fn (Menu $menu) => $menu->children->isNotEmpty() || $this->assignable($menu))
            ->values();
    }

    /** @param array $permissions [menu unique_id => ['view', 'create', ...]] */
    public function sync(User $user, array $permissions): void
    {
        DB::transaction(function () use ($user, $permissions) {
            $keep = [];

            foreach (Menu::whereIn('unique_id', array_keys($permissions))->get() as $menu) {
                if (! $this->assignable($menu)) {
                    continue;
                }

                // Any action implies being able to open the page.
                $actions = array_values(array_intersect($menu->actions, array_merge(['view'], (array) $permissions[$menu->unique_id])));
                MenuPermission::updateOrCreate(['user_id' => $user->id, 'menu_id' => $menu->id], ['actions' => $actions]);
                $keep[] = $menu->id;
            }

            MenuPermission::where('user_id', $user->id)->whereNotIn('menu_id', $keep)->get()->each->delete();
        }, 3);
    }

    private function assignable(Menu $menu): bool
    {
        return ! $menu->admin_only && ! $menu->is_public && $menu->route && ! empty($menu->actions);
    }
}
