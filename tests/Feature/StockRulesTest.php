<?php

namespace Tests\Feature;

use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockTransaction;
use App\Services\StockService;
use Tests\TestCase;

class StockRulesTest extends TestCase
{
    private function assertStock($product, float $expected): void
    {
        $product->refresh();
        $this->assertEquals($expected, (float) $product->current_stock);

        // Every change is backed by stock transactions: the two figures must always agree.
        $fromLedger = StockTransaction::where('product_id', $product->id)->sum('quantity_in') - StockTransaction::where('product_id', $product->id)->sum('quantity_out');
        $this->assertEquals($expected, round((float) $fromLedger, 3));
    }

    public function test_the_worked_example_from_the_requirements_gives_124()
    {
        $product = $this->product(['opening_stock' => 100]);
        $this->assertStock($product, 100);

        $this->postPurchase($product, 50)->assertSessionHasNoErrors();
        $this->assertStock($product, 150);

        $this->postSale($product, 20)->assertSessionHasNoErrors();
        $this->assertStock($product, 130);

        $sale = Sale::latest('id')->first();
        $this->post(route('sales-returns.store'), [
            'sale_id' => $sale->unique_id, 'return_date' => now()->toDateString(), 'reason' => 'Wrong size',
            'items' => [['sale_detail_id' => $sale->details[0]->unique_id, 'quantity' => 2, 'condition' => 'good']],
        ])->assertSessionHasNoErrors();
        $this->assertStock($product, 132);

        $purchase = Purchase::latest('id')->first();
        $this->post(route('purchase-returns.store'), [
            'purchase_id' => $purchase->unique_id, 'return_date' => now()->toDateString(), 'reason' => 'Defective',
            'items' => [['purchase_detail_id' => $purchase->details[0]->unique_id, 'quantity' => 5]],
        ])->assertSessionHasNoErrors();
        $this->assertStock($product, 127);

        $this->post(route('damages.store'), [
            'damage_date' => now()->toDateString(),
            'items' => [['product_id' => $product->unique_id, 'quantity' => 3, 'reason' => 'Broken']],
        ])->assertSessionHasNoErrors();
        $this->assertStock($product, 124);

        $summary = app(StockService::class)->summaryFor($product);
        $this->assertEquals(100, $summary->opening_qty);
        $this->assertEquals(50, $summary->purchased_qty);
        $this->assertEquals(20, $summary->sold_qty);
        $this->assertEquals(2, $summary->customer_returned_qty);
        $this->assertEquals(5, $summary->supplier_returned_qty);
        $this->assertEquals(3, $summary->damaged_qty);
    }

    public function test_a_product_cannot_be_sold_beyond_available_stock_and_nothing_is_saved()
    {
        $product = $this->product(['opening_stock' => 10]);

        $this->postSale($product, 11)->assertSessionHasErrors('stock');

        $this->assertStock($product, 10);
        $this->assertSame(0, Sale::count());
    }

    public function test_two_lines_of_the_same_product_cannot_oversell_together()
    {
        $product = $this->product(['opening_stock' => 10]);

        $this->postSale($product, 6, ['items' => [
            ['product_id' => $product->unique_id, 'quantity' => 6, 'unit_price' => 150],
            ['product_id' => $product->unique_id, 'quantity' => 6, 'unit_price' => 150],
        ]])->assertSessionHasErrors('stock');

        $this->assertStock($product, 10);
    }

    public function test_totals_are_worked_out_on_the_server_not_taken_from_the_browser()
    {
        $product = $this->product(['opening_stock' => 10]);

        $this->postSale($product, 2, [
            'discount' => 50, 'paid_amount' => 100,
            'grand_total' => 1, 'subtotal' => 1, 'due_amount' => 0,
        ])->assertSessionHasNoErrors();

        $sale = Sale::latest('id')->first();
        $this->assertEquals(300, $sale->subtotal);
        $this->assertEquals(250, $sale->grand_total);
        $this->assertEquals(100, $sale->paid_amount);
        $this->assertEquals(150, $sale->due_amount);
    }

    public function test_discount_and_paid_amount_limits_are_enforced()
    {
        $product = $this->product(['opening_stock' => 10]);

        $this->postSale($product, 1, ['discount' => 151])->assertSessionHasErrors('discount');
        $this->postSale($product, 1, ['paid_amount' => 151])->assertSessionHasErrors('paid_amount');
        $this->assertStock($product, 10);
    }

    public function test_a_damaged_customer_return_does_not_add_stock_but_is_recorded()
    {
        $product = $this->product(['opening_stock' => 10]);
        $this->postSale($product, 5);
        $sale = Sale::latest('id')->first();

        $this->post(route('sales-returns.store'), [
            'sale_id' => $sale->unique_id, 'return_date' => now()->toDateString(), 'reason' => 'Torn',
            'items' => [['sale_detail_id' => $sale->details[0]->unique_id, 'quantity' => 2, 'condition' => 'damaged']],
        ])->assertSessionHasNoErrors();

        $this->assertStock($product, 5);
        $this->assertDatabaseHas('stock_transactions', ['product_id' => $product->id, 'transaction_type' => 'sales_return_damaged', 'quantity_in' => 0]);

        $this->get(route('reports.damage-returns', ['type' => 'damage']))->assertOk()->assertSee('Customer return');
    }

    public function test_returns_cannot_exceed_what_is_left_on_the_invoice()
    {
        $product = $this->product(['opening_stock' => 10]);
        $this->postSale($product, 5);
        $sale = Sale::latest('id')->first();
        $detailId = $sale->details[0]->unique_id;
        $return = fn ($quantity) => $this->post(route('sales-returns.store'), [
            'sale_id' => $sale->unique_id, 'return_date' => now()->toDateString(), 'reason' => 'Return',
            'items' => [['sale_detail_id' => $detailId, 'quantity' => $quantity, 'condition' => 'good']],
        ]);

        $return(6)->assertSessionHasErrors('items');
        $return(3)->assertSessionHasNoErrors();
        $return(3)->assertSessionHasErrors('items');
        $return(2)->assertSessionHasNoErrors();
        $this->assertStock($product, 10);
    }

    public function test_supplier_returns_cannot_exceed_the_remaining_purchased_quantity()
    {
        $product = $this->product(['opening_stock' => 0]);
        $this->postPurchase($product, 10);
        $purchase = Purchase::latest('id')->first();
        $return = fn ($quantity) => $this->post(route('purchase-returns.store'), [
            'purchase_id' => $purchase->unique_id, 'return_date' => now()->toDateString(), 'reason' => 'Return',
            'items' => [['purchase_detail_id' => $purchase->details[0]->unique_id, 'quantity' => $quantity]],
        ]);

        $return(11)->assertSessionHasErrors('items');
        $return(4)->assertSessionHasNoErrors();
        $return(7)->assertSessionHasErrors('items');
        $this->assertStock($product, 6);
    }

    public function test_a_supplier_return_cannot_take_stock_that_has_already_been_sold()
    {
        $product = $this->product(['opening_stock' => 0]);
        $this->postPurchase($product, 10);
        $this->postSale($product, 8);
        $purchase = Purchase::latest('id')->first();

        $this->post(route('purchase-returns.store'), [
            'purchase_id' => $purchase->unique_id, 'return_date' => now()->toDateString(), 'reason' => 'Return',
            'items' => [['purchase_detail_id' => $purchase->details[0]->unique_id, 'quantity' => 5]],
        ])->assertSessionHasErrors('stock');

        $this->assertStock($product, 2);
        $this->assertEquals(0, $purchase->details[0]->fresh()->returned_quantity);
    }

    public function test_a_return_from_another_invoice_is_rejected()
    {
        $product = $this->product(['opening_stock' => 10]);
        $this->postSale($product, 1);
        $first = Sale::latest('id')->first();
        $this->postSale($product, 1);
        $second = Sale::latest('id')->first();

        $this->post(route('sales-returns.store'), [
            'sale_id' => $second->unique_id, 'return_date' => now()->toDateString(), 'reason' => 'Return',
            'items' => [['sale_detail_id' => $first->details[0]->unique_id, 'quantity' => 1, 'condition' => 'good']],
        ])->assertSessionHasErrors('items');

        $this->assertStock($product, 8);
    }

    public function test_cancelling_reverses_stock_with_new_entries_and_keeps_the_history()
    {
        $product = $this->product(['opening_stock' => 10]);
        $this->postSale($product, 4, ['paid_amount' => 100]);
        $sale = Sale::latest('id')->first();
        $this->assertStock($product, 6);

        $this->post(route('sales.cancel', $sale), ['cancel_reason' => 'Customer changed mind'])->assertSessionHasNoErrors();

        $this->assertStock($product, 10);
        $this->assertSame('cancelled', $sale->fresh()->status);
        $this->assertDatabaseHas('sales', ['id' => $sale->id]);
        $this->assertDatabaseHas('stock_transactions', ['transaction_type' => 'sale', 'quantity_out' => 4]);
        $this->assertDatabaseHas('stock_transactions', ['transaction_type' => 'sale_cancel', 'quantity_in' => 4]);
        $this->assertSame('cancelled', $sale->payments()->first()->status);
        $this->assertEquals(0, app(StockService::class)->summaryFor($product)->sold_qty);

        $this->post(route('sales.cancel', $sale), ['cancel_reason' => 'Again'])->assertSessionHasErrors('sale');
        $this->assertStock($product, 10);
    }

    public function test_cancel_needs_a_reason_and_posted_documents_have_no_delete_route()
    {
        $product = $this->product(['opening_stock' => 10]);
        $this->postSale($product, 1);
        $sale = Sale::latest('id')->first();

        $this->post(route('sales.cancel', $sale), [])->assertSessionHasErrors('cancel_reason');
        $this->delete('sales/' . $sale->id)->assertStatus(405);
        $this->put('sales/' . $sale->id, [])->assertStatus(405);
    }

    public function test_an_invoice_with_returns_must_have_them_cancelled_first()
    {
        $product = $this->product(['opening_stock' => 10]);
        $this->postSale($product, 4);
        $sale = Sale::latest('id')->first();
        $this->post(route('sales-returns.store'), [
            'sale_id' => $sale->unique_id, 'return_date' => now()->toDateString(), 'reason' => 'Return',
            'items' => [['sale_detail_id' => $sale->details[0]->unique_id, 'quantity' => 1, 'condition' => 'good']],
        ]);

        $this->post(route('sales.cancel', $sale), ['cancel_reason' => 'x'])->assertSessionHasErrors('sale');
        $this->assertStock($product, 7);

        $this->post(route('sales-returns.cancel', $sale->returns()->first()), ['cancel_reason' => 'x'])->assertSessionHasNoErrors();
        $this->assertStock($product, 6);
        $this->assertEquals(0, $sale->details[0]->fresh()->returned_quantity);

        $this->post(route('sales.cancel', $sale), ['cancel_reason' => 'x'])->assertSessionHasNoErrors();
        $this->assertStock($product, 10);
    }

    public function test_a_purchase_cannot_be_cancelled_when_its_stock_is_already_sold()
    {
        $product = $this->product(['opening_stock' => 0]);
        $this->postPurchase($product, 10);
        $this->postSale($product, 8);

        $this->post(route('purchases.cancel', Purchase::latest('id')->first()), ['cancel_reason' => 'x'])->assertSessionHasErrors('stock');
        $this->assertStock($product, 2);
    }

    public function test_stock_adjustments_change_stock_and_can_be_cancelled()
    {
        $product = $this->product(['opening_stock' => 10]);
        $post = fn ($type, $quantity) => $this->post(route('stock-adjustments.store'), [
            'adjustment_date' => now()->toDateString(), 'product_id' => $product->unique_id, 'type' => $type, 'quantity' => $quantity, 'reason' => 'Count',
        ]);

        $post('increase', 5)->assertSessionHasNoErrors();
        $this->assertStock($product, 15);
        $post('decrease', 2.5)->assertSessionHasNoErrors();
        $this->assertStock($product, 12.5);
        $post('decrease', 100)->assertSessionHasErrors('stock');
        $this->assertStock($product, 12.5);

        $this->post(route('stock-adjustments.cancel', \App\Models\StockAdjustment::latest('id')->first()), ['cancel_reason' => 'x'])->assertSessionHasNoErrors();
        $this->assertStock($product, 15);
        $this->assertEquals(5, app(StockService::class)->summaryFor($product)->adjusted_qty);
    }

    public function test_decimal_quantities_stay_exact()
    {
        $product = $this->product(['opening_stock' => 1.1]);
        $this->postSale($product, 0.7)->assertSessionHasNoErrors();
        $this->postSale($product, 0.4)->assertSessionHasNoErrors();
        $this->assertStock($product, 0);
    }

    public function test_a_product_with_transactions_cannot_be_deleted_but_a_new_one_can()
    {
        $used = $this->product(['opening_stock' => 10]);
        $this->postSale($used, 1);
        $this->delete(route('products.destroy', $used))->assertSessionHasErrors('product');
        $this->assertDatabaseHas('products', ['id' => $used->id]);

        $unused = $this->product(['opening_stock' => 10]);
        $this->delete(route('products.destroy', $unused))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('products', ['id' => $unused->id]);
    }

    public function test_editing_a_product_cannot_change_its_stock()
    {
        $product = $this->product(['opening_stock' => 10]);

        $this->put(route('products.update', $product), [
            'name' => 'Renamed', 'category_id' => $product->category->unique_id, 'unit_id' => $product->unit->unique_id,
            'purchase_price' => 1, 'sales_price' => 2, 'status' => 1, 'opening_stock' => 999, 'current_stock' => 999,
        ])->assertSessionHasNoErrors();

        $this->assertStock($product, 10);
        $this->assertSame('Renamed', $product->fresh()->name);
    }

    public function test_every_transaction_records_who_and_when()
    {
        $product = $this->product(['opening_stock' => 10]);
        $this->postSale($product, 1);

        $this->assertSame($this->admin()->id, Sale::latest('id')->first()->created_by);
        $this->assertSame(0, StockTransaction::whereNull('created_by')->count());
        $this->assertSame(0, StockTransaction::whereNull('created_at')->count());
    }
}
