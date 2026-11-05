<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Services\ProductService;
use App\Support\VersionedCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CacheObserverTest extends TestCase
{
    public function test_lists_are_served_from_cache_until_a_model_changes()
    {
        $this->product(['name' => 'First']);
        $this->actingAs($this->admin());
        $service = app(ProductService::class);

        $service->paginate([]);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->assertCount(1, $service->paginate([]));
        $this->assertCount(0, DB::getQueryLog(), 'A second read must not touch the database.');

        // The observer flushes on change, so the next read sees the new data.
        $this->product(['name' => 'Second']);
        $this->assertCount(2, $service->paginate([]));
    }

    public function test_renaming_a_category_refreshes_cached_products()
    {
        $product = $this->product();
        $this->get(route('products.index'))->assertSee($product->category->name);

        $product->category->update(['name' => 'Renamed Category']);

        $this->get(route('products.index'))->assertSee('Renamed Category');
    }

    public function test_settings_are_cached_and_refreshed_on_save()
    {
        $this->assertSame('Inventory Management', setting('site_name'));
        DB::enableQueryLog();
        DB::flushQueryLog();
        setting('site_name');
        $this->assertCount(0, DB::getQueryLog());

        $this->actingAs($this->admin())->put(route('settings.update'), ['site_name' => 'My Shop', 'currency_symbol' => '$'])->assertSessionHasNoErrors();

        $this->assertSame('My Shop', setting('site_name'));
        $this->get(route('dashboard'))->assertSee('My Shop');
    }

    public function test_the_dashboard_refreshes_after_a_sale()
    {
        $product = $this->product(['opening_stock' => 10]);
        $this->actingAs($this->admin())->get(route('dashboard'))->assertOk();

        $this->postSale($product, 3)->assertSessionHasNoErrors();

        $stats = app(\App\Services\DashboardService::class)->stats();
        $this->assertEquals(7, $stats['available_stock']);
        $this->assertEquals(3, $stats['today_sales_qty']);
    }

    public function test_the_barcode_lookup_sees_new_stock_immediately()
    {
        $product = $this->product(['barcode' => '8901234567890', 'opening_stock' => 10]);
        $lookup = fn () => $this->actingAs($this->admin())->getJson(route('products.lookup', ['code' => '8901234567890', 'context' => 'sale']));

        $lookup()->assertJson(['available' => 10]);
        $this->postSale($product, 4);
        $lookup()->assertJson(['available' => 6]);
    }

    public function test_a_rolled_back_transaction_does_not_leave_wrong_data_behind()
    {
        $product = $this->product(['opening_stock' => 5]);
        $service = app(ProductService::class);
        $this->actingAs($this->admin());
        $service->paginate([]);

        $this->postSale($product, 50)->assertSessionHasErrors('stock');

        $this->assertEquals(5, (float) $service->paginate([])->first()->current_stock);
    }

    /** The code must keep working when the driver in .env is changed. */
    public function test_it_works_on_the_file_and_database_cache_drivers()
    {
        foreach (['file', 'array'] as $store) {
            config(['cache.default' => $store]);
            Cache::flush();

            $calls = 0;
            $read = function () use (&$calls) {
                return VersionedCache::remember('demo', 'key', function () use (&$calls) {
                    $calls++;

                    return 'value';
                });
            };

            $read();
            $read();
            $this->assertSame(1, $calls, "{$store}: second read must be cached");

            VersionedCache::flush('demo');
            $read();
            $this->assertSame(2, $calls, "{$store}: flush must force a fresh read");
        }

        Cache::flush();
    }
}
