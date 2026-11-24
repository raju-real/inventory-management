<?php

namespace Tests\Feature;

use App\Models\Sale;
use Tests\TestCase;

class VatSettingsTest extends TestCase
{
    private function saveVat(array $data)
    {
        return $this->actingAs($this->admin())->put(route('settings.vat'), $data);
    }

    public function test_admin_sets_the_default_vat()
    {
        $this->saveVat(['vat_default_mode' => 'order', 'vat_default_type' => 'percentage', 'vat_default_value' => 7.5])
            ->assertRedirect(route('settings.edit', ['tab' => 'vat']))->assertSessionHasNoErrors();

        $this->assertSame('order', setting('vat_default_mode'));
        $this->assertEquals(7.5, setting('vat_default_value'));
        $this->get(route('settings.edit', ['tab' => 'vat']))->assertOk()->assertSee('VAT calculation')->assertSee('On total order');
    }

    public function test_switching_to_product_wise_keeps_the_saved_order_vat()
    {
        $this->saveVat(['vat_default_mode' => 'order', 'vat_default_type' => 'flat', 'vat_default_value' => 50]);
        // The order fields are hidden (and not sent) when product wise is chosen.
        $this->saveVat(['vat_default_mode' => 'product'])->assertSessionHasNoErrors();

        $this->assertSame('product', setting('vat_default_mode'));
        $this->assertSame('flat', setting('vat_default_type'));
        $this->assertEquals(50, setting('vat_default_value'));
    }

    public function test_settings_are_validated_and_admin_only()
    {
        $this->saveVat(['vat_default_mode' => 'order'])->assertSessionHasErrors(['vat_default_type', 'vat_default_value']);
        $this->saveVat(['vat_default_mode' => 'order', 'vat_default_type' => 'percentage', 'vat_default_value' => 101])->assertSessionHasErrors('vat_default_value');
        $this->saveVat(['vat_default_mode' => 'order', 'vat_default_type' => 'flat', 'vat_default_value' => 500])->assertSessionHasNoErrors();
        $this->saveVat(['vat_default_mode' => 'both'])->assertSessionHasErrors('vat_default_mode');

        $this->actingAs($this->staff(['sales' => ['view', 'create']]))
            ->put(route('settings.vat'), ['vat_default_mode' => 'order', 'vat_default_type' => 'flat', 'vat_default_value' => 1])->assertForbidden();
    }

    public function test_the_sale_screen_starts_with_the_default()
    {
        $page = $this->actingAs($this->admin())->get(route('sales.create'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<option value="product"\s+selected/', $page, 'Product wise by default.');

        $this->saveVat(['vat_default_mode' => 'order', 'vat_default_type' => 'flat', 'vat_default_value' => 25]);
        $page = $this->get(route('sales.create'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<option value="order"\s+selected/', $page);
        $this->assertMatchesRegularExpression('/<option value="flat"\s+selected/', $page);
        $this->assertMatchesRegularExpression('/id="vat_value"[^>]*value="25"/', $page);
    }

    public function test_a_sale_can_still_change_the_default()
    {
        $this->saveVat(['vat_default_mode' => 'order', 'vat_default_type' => 'percentage', 'vat_default_value' => 10]);
        $product = $this->product(['sales_price' => 1000, 'opening_stock' => 20]);

        // Changed on the sale: product wise (this product has no VAT).
        $this->postSale($product, 1, ['vat_mode' => 'product', 'paid_amount' => 1000])->assertSessionHasNoErrors();
        $sale = Sale::latest('id')->first();
        $this->assertSame('product', $sale->vat_mode);
        $this->assertEquals(1000, $sale->grand_total);

        // Changed on the sale: a different rate on the total.
        $this->postSale($product, 1, ['vat_mode' => 'order', 'vat_type' => 'percentage', 'vat_value' => 5, 'paid_amount' => 1050])->assertSessionHasNoErrors();
        $this->assertEquals(50, Sale::latest('id')->first()->vat_amount);
    }

    public function test_a_sale_sent_without_a_vat_choice_uses_the_default()
    {
        $this->saveVat(['vat_default_mode' => 'order', 'vat_default_type' => 'percentage', 'vat_default_value' => 10]);
        $product = $this->product(['sales_price' => 1000, 'opening_stock' => 20]);

        $this->postSale($product, 1, ['vat_mode' => null, 'paid_amount' => 1100])->assertSessionHasNoErrors();
        $sale = Sale::latest('id')->first();
        $this->assertSame('order', $sale->vat_mode);
        $this->assertEquals(100, $sale->vat_amount);
        $this->assertEquals(1100, $sale->grand_total);
    }
}
