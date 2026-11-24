<?php

namespace Tests\Feature;

use App\Models\CustomerPayment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SalesReturn;
use App\Services\LedgerService;
use Tests\TestCase;

class PaymentAndLedgerTest extends TestCase
{
    public function test_partial_payments_track_the_invoice_due_and_cannot_overpay()
    {
        $product = $this->product();
        $customer = $this->customer();
        $this->postSale($product, 2, ['paid_amount' => 100], $customer);
        $sale = Sale::latest('id')->first();
        $this->assertEquals(200, $sale->due_amount);
        $this->assertSame('Partial', $sale->payment_status);

        $pay = fn ($amount) => $this->post(route('customer-payments.store'), [
            'customer_id' => $customer->unique_id, 'sale_id' => $sale->unique_id, 'payment_date' => now()->toDateString(), 'amount' => $amount, 'method' => 'cash',
        ]);

        $pay(201)->assertSessionHasErrors('amount');
        $pay(150)->assertSessionHasNoErrors();
        $this->assertEquals(50, $sale->fresh()->due_amount);
        $pay(50)->assertSessionHasNoErrors();
        $this->assertSame('Paid', $sale->fresh()->payment_status);

        $this->post(route('customer-payments.cancel', CustomerPayment::latest('id')->first()), ['cancel_reason' => 'Bounced'])->assertSessionHasNoErrors();
        $this->assertEquals(50, $sale->fresh()->due_amount);
    }

    public function test_a_payment_cannot_be_applied_to_another_customers_invoice()
    {
        $product = $this->product();
        $this->postSale($product, 1);
        $other = $this->customer();

        $this->post(route('customer-payments.store'), [
            'customer_id' => $other->unique_id, 'sale_id' => Sale::latest('id')->first()->unique_id, 'payment_date' => now()->toDateString(), 'amount' => 10, 'method' => 'cash',
        ])->assertSessionHasErrors('amount');
    }

    public function test_customer_ledger_and_balance_add_up_including_returns_and_refunds()
    {
        $product = $this->product();
        $customer = $this->customer();
        $customer->update(['opening_balance' => 500]);

        $this->postSale($product, 2, ['paid_amount' => 100], $customer); // +300 -100
        $sale = Sale::latest('id')->first();
        $this->post(route('sales-returns.store'), [
            'sale_id' => $sale->unique_id, 'return_date' => now()->toDateString(), 'reason' => 'x', 'refund_amount' => 50,
            'items' => [['sale_detail_id' => $sale->details[0]->unique_id, 'quantity' => 1, 'condition' => 'good']], // -150 +50
        ])->assertSessionHasNoErrors();
        $this->post(route('customer-payments.store'), ['customer_id' => $customer->unique_id, 'payment_date' => now()->toDateString(), 'amount' => 200, 'method' => 'bank']); // -200

        $expected = 500 + 300 - 100 - 150 + 50 - 200;
        $this->assertEquals(400, $expected);
        $this->assertEquals($expected, $customer->fresh()->balance);

        $ledger = app(LedgerService::class)->customer($customer, []);
        $this->assertEquals(500, $ledger['opening']);
        $this->assertEquals($expected, $ledger['closing']);

        $this->get(route('reports.customer-ledger', ['party_id' => $customer->unique_id]))->assertOk()->assertSee('400.00');
        $this->get(route('customers.index'))->assertOk()->assertSee('400.00');
    }

    public function test_supplier_ledger_and_payable_add_up()
    {
        $product = $this->product();
        $supplier = $this->supplier();

        $this->postPurchase($product, 10, ['paid_amount' => 200, 'discount' => 100, 'other_charges' => 50], $supplier); // grand 950
        $purchase = Purchase::latest('id')->first();
        $this->assertEquals(950, $purchase->grand_total);
        $this->assertEquals(750, $purchase->due_amount);

        $this->post(route('purchase-returns.store'), [
            'purchase_id' => $purchase->unique_id, 'return_date' => now()->toDateString(), 'reason' => 'x', 'refund_amount' => 20,
            'items' => [['purchase_detail_id' => $purchase->details[0]->unique_id, 'quantity' => 1]], // value 90 after discount
        ])->assertSessionHasNoErrors();

        $expected = 950 - 200 - 90 + 20;
        $this->assertEquals($expected, $supplier->fresh()->balance);
        $this->assertEquals($expected, app(LedgerService::class)->supplier($supplier, [])['closing']);
        $this->assertEquals(680, $purchase->fresh()->due_amount);
    }

    public function test_returned_goods_are_valued_after_the_invoice_discount()
    {
        $product = $this->product();
        $this->postSale($product, 2, ['discount_type' => 'percentage', 'discount_value' => 10]); // 300 - 10 %
        $sale = Sale::latest('id')->first();

        $this->post(route('sales-returns.store'), [
            'sale_id' => $sale->unique_id, 'return_date' => now()->toDateString(), 'reason' => 'x',
            'items' => [['sale_detail_id' => $sale->details[0]->unique_id, 'quantity' => 1, 'condition' => 'good']],
        ])->assertSessionHasNoErrors();

        $this->assertEquals(135, SalesReturn::latest('id')->first()->total_amount);
        $this->assertEquals(135, $sale->fresh()->due_amount);
    }

    public function test_a_full_return_never_exceeds_the_invoice_because_of_rounding()
    {
        $shirt = $this->product(['sales_price' => 800]);
        $tee = $this->product(['sales_price' => 350]);
        $this->postSale($shirt, 2, ['discount_type' => 'flat', 'discount_value' => 50, 'items' => [
            ['product_id' => $shirt->unique_id, 'quantity' => 2, 'unit_price' => 800],
            ['product_id' => $tee->unique_id, 'quantity' => 1, 'unit_price' => 350],
        ]])->assertSessionHasNoErrors();
        $sale = Sale::latest('id')->first();
        $this->assertEquals(1900, $sale->grand_total);

        // Returned in two steps, one unit at a time, to give rounding every chance to add up wrongly.
        foreach ([[0, 1], [0, 1], [1, 1]] as [$line, $quantity]) {
            $this->post(route('sales-returns.store'), [
                'sale_id' => $sale->unique_id, 'return_date' => now()->toDateString(), 'reason' => 'x',
                'items' => [['sale_detail_id' => $sale->details[$line]->unique_id, 'quantity' => $quantity, 'condition' => 'good']],
            ])->assertSessionHasNoErrors();
        }

        $this->assertLessThanOrEqual(1900, (float) $sale->returns()->sum('total_amount'));
        $this->assertEquals(0, $sale->fresh()->due_amount);
    }

    public function test_refund_cannot_exceed_the_returned_value()
    {
        $product = $this->product();
        $this->postSale($product, 1);
        $sale = Sale::latest('id')->first();

        $this->post(route('sales-returns.store'), [
            'sale_id' => $sale->unique_id, 'return_date' => now()->toDateString(), 'reason' => 'x', 'refund_amount' => 151,
            'items' => [['sale_detail_id' => $sale->details[0]->unique_id, 'quantity' => 1, 'condition' => 'good']],
        ])->assertSessionHasErrors('refund_amount');

        $this->assertSame(0, SalesReturn::count());
        $this->assertEquals(99, (float) $product->fresh()->current_stock);
    }

    public function test_ledger_date_filter_carries_earlier_transactions_into_the_opening_balance()
    {
        $product = $this->product();
        $customer = $this->customer();
        $this->postSale($product, 1, ['sale_date' => now()->subDays(10)->toDateString()], $customer);
        $this->postSale($product, 1, [], $customer);

        $ledger = app(LedgerService::class)->customer($customer, ['from' => now()->subDay()->toDateString()]);

        $this->assertEquals(150, $ledger['opening']);
        $this->assertSame(1, $ledger['rows']->total());
        $this->assertEquals(300, $ledger['closing']);
    }
}
