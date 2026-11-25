<?php

use App\Models\Menu;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** Adds Reports > Due List on installs that were seeded before it existed. Admins see it at once; give staff access with Manage. */
    public function up()
    {
        $reports = Menu::where('key', 'reports')->first();
        if (! $reports || Menu::where('key', 'report-due-list')->exists()) {
            return;
        }

        $after = (int) Menu::where('key', 'report-supplier-ledger')->value('sort_order');
        Menu::create([
            'parent_id' => $reports->id,
            'name' => 'Due List',
            'key' => 'report-due-list',
            'route' => 'reports.due-list',
            'icon' => 'bi-hourglass-split',
            'actions' => ['view'],
            'admin_only' => false,
            'is_public' => false,
            'sort_order' => $after ? $after + 1 : 99,
        ]);
    }

    public function down()
    {
        Menu::where('key', 'report-due-list')->first()?->delete();
    }
};
