<?php

namespace App\Services;

use App\Models\Menu;
use Illuminate\Validation\ValidationException;

class MenuService
{
    public function update(Menu $menu, array $data): Menu
    {
        // Admins must never be able to hide the pages they need to undo this.
        if ($menu->admin_only && empty($data['is_active'])) {
            throw ValidationException::withMessages(['is_active' => 'Admin menus cannot be deactivated.']);
        }

        // A group stays a top-level menu; only single pages can move between groups.
        if (! $menu->route) {
            unset($data['parent_id']);
        }

        $menu->update($data);

        return $menu;
    }
}
