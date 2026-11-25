<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\SalesReturn;
use Tests\TestCase;

class SaleDiscountVatTest extends TestCase
{
    private function products(): array
    {
        $laptop = $this->product(['name' => 'Laptop', 'sales_price' => 1000, 'opening_stock' => 20, 'vat_type' => 'percentage', 'vat_value' => 15]);
        $mouse = $this->product(['name' => 'Mouse', 'sales_price' => 500, 'opening_stock' => 20, 'vat_type' => 'flat', 'vat_value' => 10]);

        return [$laptop, $mouse];
    }

    /** 2 laptops at 1000 with 15% VAT, 1 mouse at 500 with 10 VAT per unit. */
    private function sell(array $extra = [], ?Customer $customer = null, array $lineVat = [])
    {
        [$laptop, $mouse] = $this->products();

        return $this->postSale($laptop, 1, array_merge([
            'items' => [
                array_merge(['product_id' => $laptop->unique_id, 'quantity' => 2, 'unit_price' => 1000, 'vat_type' => 'percentage', 'vat_value' => 15], $lineVat[0] ?? []),
                array_merge(['product_id' => $mouse->unique_id, 'quantity' => 1, 'unit_price' => 500, 'vat_type' => 'flat', 'vat_value' => 10], $lineVat[1] ?? []),
            ],
        ], $extra), $customer);
    }

    public function test_percentage_discount_and_product_wise_vat()
    {
        $this->sell(['discount_type' => 'percentage', 'discount_value' => 10, 'vat_mode' => 'product', 'paid_amount' => 2530])->assertSessionHasNoErrors();
        $sale = Sale::first();

        // Subtotal 2500, discount 250 shared 200 / 50. Laptop VAT 15% of 1800 = 270, mouse 10 x 1 = 10.
        $this->assertEquals(2500, $sale->subtotal);
        $this->assertSame('percentage', $sale->discount_type);
        $this->assertEquals(10, $sale->discount_value);
        $this->assertEquals(250, $sale->discount);
        $this->assertSame('product', $sale->vat_mode);
        $this->assertEquals(280, $sale->vat_amount);
        $this->assertEquals(2530, $sale->grand_total);
        $this->assertEquals([270, 10], $sale->details->pluck('vat_amount')->map(fn ($v) => (float) $v)->all());
        $this->assertEquals(0, $sale->due_amount);
    }

    public function test_flat_discount()
    {
        $this->sell(['discount_type' => 'flat', 'discount_value' => 100, 'paid_amount' => 2698])->assertSessionHasNoErrors();
        $sale = Sale::first();

        // Discount 100 shared 80 / 20. Laptop VAT 15% of 1920 = 288, mouse 10.
        $this->assertEquals(100, $sale->discount);
        $this->assertEquals(298, $sale->vat_amount);
        $this->assertEquals(2698, $sale->grand_total);
    }

    public function test_vat_on_the_total_order()
    {
        $this->sell(['discount_type' => 'percentage', 'discount_value' => 10, 'vat_mode' => 'order', 'vat_type' => 'percentage', 'vat_value' => 5, 'paid_amount' => 2362.5])->assertSessionHasNoErrors();
        $sale = Sale::first();

        // 5% of (2500 - 250) = 112.50, shared over the lines as 90 and 22.50.
        $this->assertSame('order', $sale->vat_mode);
        $this->assertSame('percentage', $sale->vat_type);
        $this->assertEquals(5, $sale->vat_value);
        $this->assertEquals(112.5, $sale->vat_amount);
        $this->assertEquals(2362.5, $sale->grand_total);
        $this->assertEquals(112.5, (float) $sale->details->sum('vat_amount'));
    }

    public function test_flat_vat_on_the_total_order()
    {
        $this->sell(['vat_mode' => 'order', 'vat_type' => 'flat', 'vat_value' => 75, 'paid_amount' => 2575])->assertSessionHasNoErrors();
        $sale = Sale::first();

        $this->assertEquals(75, $sale->vat_amount);
        $this->assertEquals(2575, $sale->grand_total);
        $this->assertEquals(75, (float) $sale->details->sum('vat_amount'));
    }

    public function test_the_seller_can_change_the_product_vat_on_the_sale()
    {
        $this->sell(['paid_amount' => 2600], null, [['vat_value' => 5], ['vat_type' => 'percentage', 'vat_value' => 0]])->assertSessionHasNoErrors();
        $sale = Sale::first();

        // 5% of 2000 = 100, mouse none.
        $this->assertEquals(100, $sale->vat_amount);
        $this->assertEquals(2600, $sale->grand_total);
        $this->assertEquals(5, $sale->details[0]->vat_value);
    }

    public function test_figures_from_the_browser_are_ignored()
    {
        $this->sell(['discount_type' => 'flat', 'discount_value' => 0, 'paid_amount' => 2810, 'grand_total' => 1, 'vat_amount' => 0, 'discount' => 999])->assertSessionHasNoErrors();

        $this->assertEquals(2810, Sale::first()->grand_total);
    }

    public function test_limits_are_enforced()
    {
        $this->sell(['discount_type' => 'percentage', 'discount_value' => 101])->assertSessionHasErrors('discount_value');
        $this->sell(['discount_type' => 'flat', 'discount_value' => 2501])->assertSessionHasErrors('discount_value');
        $this->sell([], null, [['vat_value' => 101]])->assertSessionHasErrors('items.0.vat_value');
        $this->sell(['vat_mode' => 'order', 'vat_type' => 'percentage', 'vat_value' => 150])->assertSessionHasErrors('vat_value');
        $this->sell(['discount_type' => 'bogus', 'vat_mode' => 'bogus'])->assertSessionHasErrors(['discount_type', 'vat_mode']);
        $this->assertSame(0, Sale::count());
    }

    public function test_walk_in_sales_must_be_paid_in_full()
    {
        $walkIn = Customer::where('is_walk_in', true)->firstOrFail();

        $this->sell(['paid_amount' => 1000], $walkIn)->assertSessionHasErrors('customer_id');
        $this->assertSame(0, Sale::count());

        $this->sell(['paid_amount' => 2810], $walkIn)->assertSessionHasNoErrors();
        $this->assertSame(1, Sale::count());

        // A named customer may owe money.
        $this->sell(['paid_amount' => 0], $this->customer())->assertSessionHasNoErrors();
        $this->assertEquals(2810, Sale::latest('id')->first()->due_amount);
    }

    public function test_returns_give_back_their_share_of_vat_and_discount()
    {
        $this->sell(['discount_type' => 'percentage', 'discount_value' => 10, 'paid_amount' => 2530]);
        $sale = Sale::first();
        $return = fn ($line, $quantity) => $this->post(route('sales-returns.store'), [
            'sale_id' => $sale->unique_id, 'return_date' => now()->toDateString(), 'reason' => 'x',
            'items' => [['sale_detail_id' => $sale->details[$line]->unique_id, 'quantity' => $quantity, 'condition' => 'good']],
        ]);

        // One laptop: 1000 less 10% = 900, plus its VAT 270 / 2 = 135.
        $return(0, 1)->assertSessionHasNoErrors();
        $this->assertEquals(1035, SalesReturn::first()->total_amount);

        $return(0, 1)->assertSessionHasNoErrors();
        $return(1, 1)->assertSessionHasNoErrors();
        $this->assertEquals(2530, (float) $sale->returns()->sum('total_amount'), 'A full return equals the grand total.');
    }

    public function test_products_store_their_vat_and_the_picker_sends_it()
    {
        [$laptop, $mouse] = $this->products();
        $this->assertSame('percentage', $laptop->vat_type);
        $this->assertEquals(15, $laptop->vat_value);

        $this->getJson(route('products.lookup', ['code' => $mouse->sku, 'context' => 'sale']))->assertJson(['vat_type' => 'flat', 'vat_value' => 10]);

        $this->post(route('products.store'), [
            'name' => 'Bad VAT', 'category_id' => $laptop->category->unique_id, 'unit_id' => $laptop->unit->unique_id,
            'purchase_price' => 1, 'sales_price' => 1, 'status' => 1, 'vat_type' => 'percentage', 'vat_value' => 120,
        ])->assertSessionHasErrors('vat_value');
        $this->post(route('products.store'), [
            'name' => 'Bad type', 'category_id' => $laptop->category->unique_id, 'unit_id' => $laptop->unit->unique_id,
            'purchase_price' => 1, 'sales_price' => 1, 'status' => 1, 'vat_type' => 'other',
        ])->assertSessionHasErrors('vat_type');

        $this->get(route('products.show', $laptop))->assertSee('15%');
    }

    public function test_invoice_receipt_and_page_show_discount_and_vat()
    {
        $this->sell(['discount_type' => 'percentage', 'discount_value' => 10, 'paid_amount' => 2530]);
        $sale = Sale::first();

        $this->get(route('sales.show', $sale))->assertSee('Discount (10%)')->assertSee('VAT (product wise)')->assertSee('280.00');
        $this->assertStringStartsWith('%PDF', $this->get(route('sales.pdf', $sale))->assertOk()->getContent());
        $this->assertStringStartsWith('%PDF', $this->get(route('sales.receipt', $sale))->assertOk()->getContent());
        $this->assertSame('VAT (product wise)', $sale->vat_label);
        $this->assertSame('Discount (10%)', $sale->discount_label);
    }

    public function test_the_sale_page_offers_direct_printing()
    {
        $this->sell(['paid_amount' => 2810]);
        $sale = Sale::first();

        $page = $this->get(route('sales.show', $sale))->assertOk();
        $page->assertSee('data-direct-print="' . route('sales.receipt', $sale) . '"', false)->assertSee('data-shortcut="F6"', false);
        $this->get(route('sales.index'))->assertSee('data-direct-print="' . route('sales.receipt', $sale) . '"', false);
    }
}
