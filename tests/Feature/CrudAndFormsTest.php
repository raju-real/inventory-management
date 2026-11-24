<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CrudAndFormsTest extends TestCase
{
    public function test_every_form_input_has_a_placeholder()
    {
        $product = $this->product();
        $customer = $this->customer();
        $supplier = $this->supplier();
        $this->postSale($product, 1, [], $customer);
        $this->postPurchase($product, 1, [], $supplier);
        $sale = Sale::first();
        $purchase = \App\Models\Purchase::first();

        $pages = [
            route('categories.create'), route('categories.edit', Category::first()), route('categories.index'),
            route('units.create'), route('units.edit', Unit::first()), route('units.index'),
            route('products.create'), route('products.edit', $product), route('products.index'), route('products.labels', $product),
            route('customers.create'), route('customers.edit', $customer), route('customers.index'),
            route('suppliers.create'), route('suppliers.edit', $supplier), route('suppliers.index'),
            route('purchases.create'), route('purchases.index'), route('purchases.show', $purchase),
            route('sales.create'), route('sales.index'), route('sales.show', $sale),
            route('sales-returns.create'), route('sales-returns.create', ['invoice' => $sale->reference_no]), route('sales-returns.index'),
            route('purchase-returns.create'), route('purchase-returns.create', ['invoice' => $purchase->reference_no]), route('purchase-returns.index'),
            route('damages.create'), route('damages.index'),
            route('stock.index'), route('stock.ledger'), route('stock-adjustments.create'), route('stock-adjustments.index'),
            route('customer-payments.create'), route('customer-payments.index'), route('supplier-payments.create'), route('supplier-payments.index'),
            route('reports.stock'), route('reports.low-stock'), route('reports.purchases'), route('reports.sales'), route('reports.damage-returns'),
            route('reports.customer-ledger'), route('reports.supplier-ledger'),
            route('users.create'), route('users.edit', $this->admin()), route('users.index'),
            route('menus.edit', \App\Models\Menu::first()),
            route('settings.edit'), route('settings.edit', ['tab' => 'mail']), route('barcode.settings'), route('profile.edit'),
        ];

        $checked = 0;
        foreach ($pages as $url) {
            $html = $this->actingAs($this->admin())->get($url)->assertOk()->getContent();
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);

            foreach (['input', 'textarea'] as $tag) {
                foreach ($dom->getElementsByTagName($tag) as $input) {
                    if (in_array($input->getAttribute('type'), ['hidden', 'checkbox', 'radio', 'submit', 'button'], true)) {
                        continue;
                    }
                    $this->assertNotSame('', $input->getAttribute('placeholder'), "Missing placeholder on {$url}: " . $input->getAttribute('name'));
                    $checked++;
                }
            }
        }

        $this->assertGreaterThan(80, $checked);
    }

    public function test_every_submit_button_and_add_link_uses_the_btn_submit_class()
    {
        $views = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'), \FilesystemIterator::SKIP_DOTS));
        $checked = 0;

        foreach ($views as $file) {
            $source = file_get_contents((string) $file);

            preg_match_all('/<button\b[^>]*type="submit"[^>]*>/', $source, $buttons);
            preg_match_all('/<a class="btn [^"]*"\s+href="\{\{ route\([^"]*\.create[^>]*>/', $source, $links);

            foreach (array_merge($buttons[0], $links[0]) as $tag) {
                $this->assertStringContainsString('btn-submit', $tag, "Missing btn-submit in {$file}: {$tag}");
                $checked++;
            }
        }

        $this->assertGreaterThan(50, $checked);
    }

    public function test_lists_are_paginated()
    {
        for ($i = 1; $i <= 20; $i++) {
            Customer::create(['name' => "Customer {$i}", 'status' => true]);
            Supplier::create(['name' => "Supplier {$i}", 'status' => true]);
            Category::create(['name' => "Category {$i}"]);
            Unit::create(['name' => "Unit {$i}"]);
        }

        $this->actingAs($this->admin());
        foreach (['customers', 'suppliers', 'categories', 'units'] as $resource) {
            $response = $this->get(route($resource . '.index'))->assertOk();
            $response->assertSee('page=2', false);
            $this->get(route($resource . '.index', ['page' => 2]))->assertOk();
        }
    }

    public function test_transaction_lists_and_reports_are_paginated()
    {
        $product = $this->product(['opening_stock' => 100]);
        $customer = $this->customer();
        for ($i = 0; $i < 17; $i++) {
            $this->postSale($product, 1, [], $customer)->assertSessionHasNoErrors();
        }

        foreach ([
            route('sales.index'), route('stock.ledger'), route('reports.sales'),
            route('reports.customer-ledger', ['party_id' => $customer->unique_id]), route('customer-payments.index', ['x' => 1]),
        ] as $url) {
            $response = $this->get($url)->assertOk();
            if (! str_contains($url, 'customer-payments')) {
                $response->assertSee('page=2', false);
            }
        }

        $this->get(route('reports.sales', ['group' => 'date']))->assertOk();
        $this->get(route('reports.sales', ['group' => 'party']))->assertOk()->assertSee('Rahim');
    }

    public function test_required_fields_are_validated_on_the_server()
    {
        $this->actingAs($this->admin());

        $this->post(route('categories.store'), [])->assertSessionHasErrors(['name', 'status']);
        $this->post(route('units.store'), [])->assertSessionHasErrors(['name', 'status']);
        $this->post(route('products.store'), [])->assertSessionHasErrors(['name', 'category_id', 'unit_id', 'purchase_price', 'sales_price', 'status']);
        $this->post(route('customers.store'), ['mobile' => 'abc', 'email' => 'bad'])->assertSessionHasErrors(['name', 'status', 'mobile', 'email']);
        $this->post(route('suppliers.store'), ['mobile' => 'abc'])->assertSessionHasErrors(['name', 'status', 'mobile']);
        $this->post(route('purchases.store'), [])->assertSessionHasErrors(['purchase_date', 'supplier_id', 'items']);
        $this->post(route('sales.store'), [])->assertSessionHasErrors(['sale_date', 'customer_id', 'items']);
        $this->post(route('damages.store'), [])->assertSessionHasErrors(['damage_date', 'items']);
        $this->post(route('sales-returns.store'), [])->assertSessionHasErrors(['sale_id', 'return_date', 'reason', 'items']);
        $this->post(route('purchase-returns.store'), [])->assertSessionHasErrors(['purchase_id', 'return_date', 'reason', 'items']);
        $this->post(route('stock-adjustments.store'), [])->assertSessionHasErrors(['adjustment_date', 'product_id', 'type', 'quantity', 'reason']);
        $this->post(route('customer-payments.store'), [])->assertSessionHasErrors(['customer_id', 'payment_date', 'amount', 'method']);
        $this->post(route('supplier-payments.store'), [])->assertSessionHasErrors(['supplier_id', 'payment_date', 'amount', 'method']);
        $this->post(route('users.store'), [])->assertSessionHasErrors(['name', 'email', 'role', 'status', 'password']);
        $this->put(route('settings.update'), [])->assertSessionHasErrors(['site_name', 'currency_symbol']);
        $this->put(route('settings.mail'), [])->assertSessionHasErrors(['mail_host', 'mail_port', 'mail_from_address']);
    }

    public function test_future_dates_are_allowed_and_bad_dates_are_rejected()
    {
        $product = $this->product();

        $this->postSale($product, 1, ['sale_date' => now()->addDays(5)->toDateString()])->assertSessionHasNoErrors();
        $this->assertEquals(now()->addDays(5)->toDateString(), Sale::first()->sale_date->toDateString());

        $this->postSale($product, 1, ['sale_date' => '04/11/2026'])->assertSessionHasErrors('sale_date');
        $this->postSale($product, 1, ['sale_date' => 'not a date'])->assertSessionHasErrors('sale_date');
    }

    public function test_a_party_can_be_added_from_the_invoice_screen()
    {
        $this->actingAs($this->admin());

        $this->postJson(route('customers.store'), ['mobile' => '01700000001', 'name' => 'New Buyer', 'mobile' => '01700000000', 'status' => 1])
            ->assertCreated()->assertJson(['name' => 'New Buyer', 'label' => 'New Buyer (01700000000)']);
        $this->postJson(route('suppliers.store'), ['name' => 'New Vendor', 'status' => 1])
            ->assertCreated()->assertJson(['label' => 'New Vendor']);

        $this->postJson(route('customers.store'), ['status' => 1, 'mobile' => 'abc'])
            ->assertStatus(422)->assertJsonValidationErrors(['name', 'mobile']);

        // The new customer appears in the dropdown straight away, despite the cache.
        $this->get(route('sales.create'))->assertSee('New Buyer (01700000000)');
    }

    public function test_the_add_button_and_top_bar_links_follow_permissions()
    {
        $this->actingAs($this->admin())->get(route('sales.create'))->assertSee('id="partyModal"', false)->assertSee('New Purchase');

        $clerk = $this->staff(['sales' => ['view', 'create']]);
        $page = $this->actingAs($clerk)->get(route('sales.create'))->assertOk();
        $page->assertDontSee('id="partyModal"', false)->assertSee('New Sale')->assertDontSee('New Purchase');
        $this->postJson(route('customers.store'), ['mobile' => '01700000001', 'name' => 'Sneaky', 'status' => 1])->assertForbidden();

        $viewer = $this->staff(['products' => ['view']]);
        $this->actingAs($viewer)->get(route('products.index'))->assertDontSee('New Sale')->assertDontSee('New Purchase');
    }

    public function test_bad_line_items_are_rejected()
    {
        $product = $this->product();

        $this->postSale($product, 0)->assertSessionHasErrors('items.0.quantity');
        $this->postSale($product, -1)->assertSessionHasErrors('items.0.quantity');

        $this->postSale($product, 1, ['items' => [['product_id' => 99999, 'quantity' => 1, 'unit_price' => 1]]])->assertSessionHasErrors('items.0.product_id');

        $product->update(['status' => false]);
        $this->postSale($product, 1)->assertSessionHasErrors('items.0.product_id');
        $this->assertSame(0, Sale::count());
    }

    public function test_html_in_user_input_is_escaped()
    {
        $this->actingAs($this->admin())->post(route('customers.store'), ['mobile' => '01700000001', 'name' => '<script>alert(1)</script>', 'status' => 1])->assertSessionHasNoErrors();

        $this->get(route('customers.index'))->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
    }

    public function test_images_are_optional_stored_replaced_and_removed()
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $this->post(route('customers.store'), ['mobile' => '01700000001', 'name' => 'No Image', 'status' => 1])->assertSessionHasNoErrors();
        $this->post(route('customers.store'), ['mobile' => '01700000001', 'name' => 'Has Image', 'status' => 1, 'image' => UploadedFile::fake()->image('a.jpg')])->assertSessionHasNoErrors();
        $customer = Customer::where('name', 'Has Image')->first();
        Storage::disk('public')->assertExists($customer->image);
        $old = $customer->image;

        $this->put(route('customers.update', $customer), ['mobile' => '01700000001', 'name' => 'Has Image', 'status' => 1, 'image' => UploadedFile::fake()->image('b.png')])->assertSessionHasNoErrors();
        $customer->refresh();
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($customer->image);

        // Saving without a new file keeps the image.
        $this->put(route('customers.update', $customer), ['mobile' => '01700000001', 'name' => 'Renamed', 'status' => 1])->assertSessionHasNoErrors();
        $this->assertNotNull($customer->fresh()->image);

        $this->put(route('customers.update', $customer), ['mobile' => '01700000001', 'name' => 'Renamed', 'status' => 1, 'remove_image' => 1])->assertSessionHasNoErrors();
        $this->assertNull($customer->fresh()->image);

        $this->post(route('customers.store'), ['mobile' => '01700000001', 'name' => 'Bad', 'status' => 1, 'image' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])->assertSessionHasErrors('image');
        $this->post(route('suppliers.store'), ['name' => 'Big', 'status' => 1, 'image' => UploadedFile::fake()->image('big.jpg')->size(3000)])->assertSessionHasErrors('image');
        $this->post(route('suppliers.store'), ['name' => 'Svg', 'status' => 1, 'image' => UploadedFile::fake()->create('x.svg', 1, 'image/svg+xml')])->assertSessionHasErrors('image');

        $product = $this->product(['image' => UploadedFile::fake()->image('p.jpg')]);
        Storage::disk('public')->assertExists($product->image);
    }

    public function test_site_settings_logo_and_favicon_are_saved_and_used()
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->put(route('settings.update'), [
            'site_name' => 'Best Shop', 'currency_symbol' => 'Tk', 'site_address' => 'Dhaka', 'site_phone' => '01712345678', 'site_email' => 'a@b.com',
            'site_logo' => UploadedFile::fake()->image('logo.png'), 'site_favicon' => UploadedFile::fake()->image('fav.png'),
        ])->assertSessionHasNoErrors();

        Storage::disk('public')->assertExists(setting('site_logo'));
        Storage::disk('public')->assertExists(setting('site_favicon'));
        $this->assertSame('Tk 1,000.00', money(1000));

        $page = $this->get(route('dashboard'))->assertOk();
        $page->assertSee('storage/' . setting('site_logo'), false)->assertSee('rel="icon"', false)->assertSee('Best Shop');
    }

    public function test_mail_password_is_stored_encrypted_and_kept_when_left_blank()
    {
        $this->actingAs($this->admin());
        $base = ['mail_host' => 'smtp.example.com', 'mail_port' => 587, 'mail_from_address' => 'a@b.com'];

        $this->put(route('settings.mail'), $base + ['mail_password' => 'top-secret'])->assertSessionHasNoErrors();
        $stored = setting('mail_password');
        $this->assertNotSame('top-secret', $stored);
        $this->assertSame('top-secret', \Illuminate\Support\Facades\Crypt::decryptString($stored));
        $this->get(route('settings.edit', ['tab' => 'mail']))->assertDontSee($stored)->assertDontSee('top-secret');

        $this->put(route('settings.mail'), $base + ['mail_password' => ''])->assertSessionHasNoErrors();
        $this->assertSame($stored, setting('mail_password'));
    }

    public function test_master_data_in_use_cannot_be_deleted()
    {
        $product = $this->product();
        $customer = $this->customer();
        $supplier = $this->supplier();
        $this->postSale($product, 1, [], $customer);
        $this->postPurchase($product, 1, [], $supplier);

        $this->delete(route('categories.destroy', $product->category))->assertSessionHasErrors('category');
        $this->delete(route('units.destroy', $product->unit))->assertSessionHasErrors('unit');
        $this->delete(route('customers.destroy', $customer))->assertSessionHasErrors('customer');
        $this->delete(route('suppliers.destroy', $supplier))->assertSessionHasErrors('supplier');

        $free = Customer::create(['name' => 'Free', 'status' => true]);
        $this->delete(route('customers.destroy', $free))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('customers', ['id' => $free->id]);
    }

    public function test_failed_entries_bring_the_line_items_back()
    {
        $product = $this->product(['opening_stock' => 1]);

        $this->from(route('sales.create'))->postSale($product, 5)->assertRedirect(route('sales.create'));

        $page = $this->get(route('sales.create'))->assertOk();
        $page->assertSee(e($product->name), false)->assertSee('Insufficient stock');
    }

    public function test_low_stock_notification_counts_products_at_or_below_minimum()
    {
        $low = $this->product(['name' => 'Almost Gone', 'opening_stock' => 3, 'min_stock' => 5]);
        $this->product(['name' => 'Plenty', 'opening_stock' => 100, 'min_stock' => 5]);
        $this->product(['name' => 'No Minimum', 'opening_stock' => 0, 'min_stock' => 0]);

        $this->assertSame(1, Product::lowStock()->count());
        $this->get(route('reports.low-stock'))->assertOk()->assertSee('Almost Gone')->assertDontSee('Plenty');
        $this->get(route('dashboard'))->assertOk()->assertSee('Almost Gone');
        $this->assertTrue($low->is_low_stock);
    }
}
