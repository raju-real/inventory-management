<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PointTransaction;
use App\Models\Sale;
use App\Models\Setting;
use App\Services\LedgerService;
use Tests\TestCase;

class LoyaltyPointsTest extends TestCase
{
    private function points(Customer $customer): int
    {
        $customer->refresh();
        // The stored balance must always equal the sum of its transactions.
        $this->assertSame((int) $customer->points, (int) PointTransaction::where('customer_id', $customer->id)->sum('points'));

        return (int) $customer->points;
    }

    private function sale(Customer $customer, float $price, array $extra = [])
    {
        $product = $this->product(['sales_price' => $price, 'opening_stock' => 50]);

        return $this->postSale($product, 1, array_merge(['paid_amount' => $price], $extra), $customer);
    }

    public function test_a_named_customer_earns_one_point_per_full_100()
    {
        $customer = $this->customer();

        $this->sale($customer, 250)->assertSessionHasNoErrors();
        $this->assertSame(2, $this->points($customer));
        $this->assertSame(2, Sale::first()->points_earned);

        $this->sale($customer, 99.99)->assertSessionHasNoErrors();
        $this->assertSame(2, $this->points($customer));
    }

    public function test_walk_in_sales_earn_nothing()
    {
        $walkIn = Customer::where('is_walk_in', true)->first();

        $this->sale($walkIn, 1000)->assertSessionHasNoErrors();

        $this->assertSame(0, $this->points($walkIn));
        $this->assertSame(0, PointTransaction::count());
    }

    public function test_points_pay_part_of_a_sale()
    {
        $customer = $this->customer();
        $customer->forceFill(['points' => 0])->save();
        $this->sale($customer, 5000); // earns 50
        $this->assertSame(50, $this->points($customer));

        // 20 points at 0.70 = 14.00 off a 1000 sale; the rest, 986, is paid.
        $this->sale($customer, 1000, ['points_redeemed' => 20, 'paid_amount' => 986])->assertSessionHasNoErrors();
        $sale = Sale::latest('id')->first();

        $this->assertSame(20, $sale->points_redeemed);
        $this->assertEquals(14, $sale->points_amount);
        $this->assertEquals(1000, $sale->grand_total, 'Points pay for the sale; they do not change its total.');
        $this->assertEquals(0, $sale->due_amount);
        // Points are earned only on money paid: 986 earns 9.
        $this->assertSame(9, $sale->points_earned);
        $this->assertSame(50 - 20 + 9, $this->points($customer));
    }

    public function test_points_cannot_exceed_the_balance_or_the_sale()
    {
        $customer = $this->customer();
        $this->sale($customer, 500); // 5 points

        $this->sale($customer, 1000, ['points_redeemed' => 6, 'paid_amount' => 995.8])->assertSessionHasErrors('points_redeemed');

        Setting::updateOrCreate(['key' => 'points_value'], ['value' => '300']);
        // 5 points x 300 = 1500, more than a 1000 sale.
        $this->sale($customer, 1000, ['points_redeemed' => 5, 'paid_amount' => 0])->assertSessionHasErrors('points_redeemed');
        $this->sale($customer, 1000, ['points_redeemed' => 3, 'paid_amount' => 100])->assertSessionHasNoErrors();
        $this->assertSame(5 - 3 + 1, $this->points($customer));
    }

    public function test_settings_control_earning_and_using()
    {
        $customer = $this->customer();
        $this->actingAs($this->admin())->put(route('settings.points'), ['points_earn_enabled' => 1, 'points_earn_amount' => 50, 'points_redeem_enabled' => 0, 'points_value' => 1])
            ->assertSessionHasNoErrors();

        $this->sale($customer, 250);
        $this->assertSame(5, $this->points($customer));
        $this->sale($customer, 100, ['points_redeemed' => 2, 'paid_amount' => 98])->assertSessionHasErrors('points_redeemed');

        $this->put(route('settings.points'), ['points_earn_enabled' => 0, 'points_earn_amount' => 50, 'points_redeem_enabled' => 1, 'points_value' => 1]);
        $this->sale($customer, 1000, ['points_redeemed' => 2, 'paid_amount' => 998])->assertSessionHasNoErrors();
        $this->assertSame(3, $this->points($customer));

        $this->put(route('settings.points'), ['points_earn_enabled' => 1, 'points_earn_amount' => 0, 'points_redeem_enabled' => 1, 'points_value' => 0])
            ->assertSessionHasErrors(['points_earn_amount', 'points_value']);
        $this->actingAs($this->staff(['sales' => ['view', 'create']]))->put(route('settings.points'), [])->assertForbidden();
    }

    public function test_walk_in_cannot_use_points()
    {
        $walkIn = Customer::where('is_walk_in', true)->first();
        $walkIn->forceFill(['points' => 100])->save();

        $this->sale($walkIn, 1000, ['points_redeemed' => 10, 'paid_amount' => 993])->assertSessionHasErrors('points_redeemed');
    }

    public function test_cancelling_a_sale_puts_points_back()
    {
        $customer = $this->customer();
        $this->sale($customer, 5000); // 50
        $this->sale($customer, 1000, ['points_redeemed' => 20, 'paid_amount' => 986]); // -20 +9
        $this->assertSame(39, $this->points($customer));

        $this->post(route('sales.cancel', Sale::latest('id')->first()), ['cancel_reason' => 'x'])->assertSessionHasNoErrors();
        $this->assertSame(50, $this->points($customer));
    }

    public function test_returns_take_back_earned_points_in_proportion()
    {
        $customer = $this->customer();
        $product = $this->product(['sales_price' => 500, 'opening_stock' => 50]);
        $this->postSale($product, 4, ['paid_amount' => 2000], $customer); // 20 points
        $sale = Sale::first();
        $return = fn ($quantity) => $this->post(route('sales-returns.store'), [
            'sale_id' => $sale->unique_id, 'return_date' => now()->toDateString(), 'reason' => 'x',
            'items' => [['sale_detail_id' => $sale->details[0]->unique_id, 'quantity' => $quantity, 'condition' => 'good']],
        ]);

        $return(1)->assertSessionHasNoErrors(); // a quarter back: 5 points
        $this->assertSame(15, $this->points($customer));

        $this->post(route('sales-returns.cancel', $sale->returns()->first()), ['cancel_reason' => 'x']);
        $this->assertSame(20, $this->points($customer), 'Cancelling the return gives the points back.');

        $return(4)->assertSessionHasNoErrors();
        $this->assertSame(0, $this->points($customer));
        $this->assertSame(20, $sale->fresh()->points_reversed);
    }

    public function test_ledger_and_balance_count_points_as_payment()
    {
        $customer = $this->customer();
        $this->sale($customer, 5000);
        $this->sale($customer, 1000, ['points_redeemed' => 20, 'paid_amount' => 500]);

        // Owes 1000 - 14 (points) - 500 = 486.
        $this->assertEquals(486, Sale::latest('id')->first()->due_amount);
        $this->assertEquals(486, $customer->fresh()->balance);
        $ledger = app(LedgerService::class)->customer($customer, []);
        $this->assertEquals(486, $ledger['closing']);
        $this->assertTrue(collect($ledger['rows']->items())->contains(fn ($row) => str_starts_with($row['type'], 'Points used')));
    }

    public function test_customer_name_and_mobile_are_required()
    {
        $this->actingAs($this->admin());
        $this->post(route('customers.store'), ['status' => 1])->assertSessionHasErrors(['name', 'mobile']);
        $this->postJson(route('customers.store'), ['name' => 'No Mobile', 'status' => 1])->assertStatus(422)->assertJsonValidationErrors('mobile');

        // Walk-in Customer itself has no mobile and can still be edited.
        $walkIn = Customer::where('is_walk_in', true)->first();
        $this->put(route('customers.update', $walkIn), ['name' => 'Walk-in Customer', 'status' => 1])->assertSessionHasNoErrors();

        // Suppliers are unchanged.
        $this->post(route('suppliers.store'), ['name' => 'Supplier', 'status' => 1])->assertSessionHasNoErrors();
    }

    public function test_points_show_on_the_receipt_invoice_customer_page_and_reports()
    {
        $customer = $this->customer();
        $this->sale($customer, 5000);
        $this->sale($customer, 1000, ['points_redeemed' => 20, 'paid_amount' => 986, 'discount_type' => 'flat', 'discount_value' => 0]);
        $sale = Sale::latest('id')->first();

        $this->get(route('sales.show', $sale))->assertSee('Points used (20)')->assertSee('Points balance')->assertSee('39');
        $this->get(route('customers.show', $customer))->assertSee('Loyalty points')->assertSee('Used on a sale');
        $this->get(route('customers.index'))->assertSee('Points');
        $this->assertStringStartsWith('%PDF', $this->get(route('sales.receipt', $sale))->assertOk()->getContent());
        $this->assertStringStartsWith('%PDF', $this->get(route('sales.pdf', $sale))->assertOk()->getContent());

        $report = $this->get(route('reports.sales'))->assertOk();
        $report->assertSee('Discount')->assertSee('VAT')->assertSee('Points used')->assertSee('Points earned')->assertSee('14.00');
        $this->get(route('reports.sales', ['group' => 'date']))->assertOk()->assertSee('Points used');
        $this->get(route('reports.sales', ['group' => 'party']))->assertOk()->assertSee('Points used');
        $this->get(route('reports.purchases'))->assertOk()->assertSee('Other charges')->assertDontSee('Points used');
    }

    public function test_the_sale_screen_gets_balances_and_settings()
    {
        $customer = $this->customer();
        $customer->forceFill(['points' => 42])->save();

        $page = $this->actingAs($this->admin())->get(route('sales.create'))->assertOk();
        $page->assertSee('id="points-box"', false)->assertSee('data-point-value="0.7"', false);
        $this->assertStringContainsString('&quot;' . $customer->unique_id . '&quot;:42', $page->getContent());
    }
}
