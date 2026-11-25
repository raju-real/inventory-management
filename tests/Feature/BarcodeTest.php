<?php

namespace Tests\Feature;

use App\Models\Product;
use Tests\TestCase;

class BarcodeTest extends TestCase
{
    public function test_a_scan_finds_the_product_by_barcode_or_sku()
    {
        $product = $this->product(['barcode' => '8901234567890', 'sku' => 'TS-001', 'sales_price' => 150, 'purchase_price' => 100]);
        $this->actingAs($this->admin());

        $this->getJson(route('products.lookup', ['code' => '8901234567890', 'context' => 'sale']))
            ->assertOk()->assertJson(['id' => $product->unique_id, 'price' => 150, 'available' => 100]);

        $this->getJson(route('products.lookup', ['code' => 'TS-001', 'context' => 'purchase']))
            ->assertOk()->assertJson(['id' => $product->unique_id, 'price' => 100]);
    }

    public function test_an_unknown_or_inactive_code_is_not_found()
    {
        $this->product(['barcode' => 'INACTIVE1', 'status' => 0]);
        $this->actingAs($this->admin());

        $this->getJson(route('products.lookup', ['code' => 'NOPE', 'context' => 'sale']))->assertNotFound();
        $this->getJson(route('products.lookup', ['code' => 'INACTIVE1', 'context' => 'sale']))->assertNotFound();
    }

    public function test_name_search_finds_products_and_skips_inactive_ones()
    {
        $this->product(['name' => 'Blue Shirt', 'sku' => 'BS-1']);
        $this->product(['name' => 'Blue Hidden', 'status' => 0]);
        $this->actingAs($this->admin());

        $this->getJson(route('products.search', ['q' => 'blue', 'context' => 'sale']))->assertOk()->assertJsonCount(1)->assertJsonPath('0.name', 'Blue Shirt');
        $this->getJson(route('products.search', ['q' => 'BS-1', 'context' => 'sale']))->assertJsonCount(1);
        $this->getJson(route('products.search', ['q' => '', 'context' => 'sale']))->assertJsonCount(0);
    }

    public function test_the_lookup_requires_permission_for_that_screen()
    {
        $this->product(['barcode' => '111222333']);
        $sales = $this->staff(['sales' => ['view', 'create']]);

        $this->actingAs($sales)->getJson(route('products.lookup', ['code' => '111222333', 'context' => 'sale']))->assertOk();
        $this->getJson(route('products.lookup', ['code' => '111222333', 'context' => 'purchase']))->assertForbidden();
        $this->getJson(route('products.lookup', ['code' => '111222333', 'context' => 'hack']))->assertStatus(422);
    }

    public function test_sales_staff_do_not_see_the_purchase_price()
    {
        $this->product(['barcode' => '111222333', 'sales_price' => 150, 'purchase_price' => 100]);
        $sales = $this->staff(['sales' => ['view', 'create']]);

        $response = $this->actingAs($sales)->getJson(route('products.lookup', ['code' => '111222333', 'context' => 'sale']));

        $response->assertJson(['price' => 150]);
        $this->assertArrayNotHasKey('purchase_price', $response->json());
    }

    public function test_barcodes_and_skus_must_be_unique_and_sku_is_generated_when_blank()
    {
        $first = $this->product(['barcode' => 'SAME123']);
        $this->assertStringStartsWith('PRD-', $first->sku);

        $this->post(route('products.store'), [
            'name' => 'Other', 'barcode' => 'SAME123', 'sku' => $first->sku, 'category_id' => $first->category->unique_id, 'unit_id' => $first->unit->unique_id,
            'purchase_price' => 1, 'sales_price' => 1, 'status' => 1,
        ])->assertSessionHasErrors(['barcode', 'sku']);

        // Saving a product without changing its own codes is fine.
        $this->put(route('products.update', $first), [
            'name' => 'Same', 'barcode' => 'SAME123', 'sku' => $first->sku, 'category_id' => $first->category->unique_id, 'unit_id' => $first->unit->unique_id,
            'purchase_price' => 1, 'sales_price' => 1, 'status' => 1,
        ])->assertSessionHasNoErrors();
    }

    public function test_the_create_link_from_a_failed_scan_prefills_the_barcode()
    {
        $this->actingAs($this->admin())->get(route('products.create', ['barcode' => '999888777']))->assertOk()->assertSee('value="999888777"', false);
    }

    public function test_labels_print_a_real_barcode_image()
    {
        $product = $this->product(['barcode' => '8901234567890']);

        $this->get(route('products.labels', [$product, 'copies' => 3]))
            ->assertOk()->assertSee('<svg', false)->assertSee('8901234567890');
    }

    public function test_label_options_show_or_hide_the_price_and_change_the_size()
    {
        $product = $this->product(['barcode' => '8901234567890', 'sales_price' => 1650]);
        $this->actingAs($this->admin());

        $shown = $this->get(route('products.labels', [$product, 'copies' => 2]))->assertOk();
        $shown->assertSee('class="sheet size-50x30 layout-sheet"', false)->assertSee('id="show_price" name="show_price" value="1" checked', false);
        $this->assertSame(2, substr_count($shown->getContent(), '<div class="label">'));

        $this->get(route('products.labels', [$product, 'show_price' => 0, 'show_shop' => 0, 'size' => '38x25', 'layout' => 'roll']))
            ->assertOk()->assertSee('class="sheet size-38x25 layout-roll hide-price hide-shop"', false)
            ->assertDontSee('id="show_price" name="show_price" value="1" checked', false);

        $this->get(route('products.labels', [$product, 'size' => '99x99']))->assertSessionHasErrors('size');
    }

    public function test_barcode_settings_are_validated_and_reach_the_page()
    {
        $this->actingAs($this->admin());

        $this->put(route('barcode.settings.update'), ['barcode_enabled' => 1, 'barcode_min_length' => 0, 'barcode_max_interval' => 5, 'barcode_suffix' => 'x', 'barcode_beep' => 1])
            ->assertSessionHasErrors(['barcode_min_length', 'barcode_max_interval', 'barcode_suffix']);

        $this->put(route('barcode.settings.update'), ['barcode_enabled' => 1, 'barcode_min_length' => 6, 'barcode_max_interval' => 120, 'barcode_suffix' => 'tab', 'barcode_beep' => 0])
            ->assertSessionHasNoErrors();

        $this->get(route('sales.create'))->assertSee('minLength: 6', false)->assertSee('maxInterval: 120', false)->assertSee('suffix: "tab"', false)->assertSee('beep: false', false);
    }
}
