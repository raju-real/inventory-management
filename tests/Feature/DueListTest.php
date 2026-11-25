<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Sale;
use App\Services\DueService;
use Tests\TestCase;

class DueListTest extends TestCase
{
    private Customer $rahim;
    private Customer $karim;
    private Customer $paidUp;

    protected function setUp(): void
    {
        parent::setUp();
        $product = $this->product(['sales_price' => 1000, 'opening_stock' => 100]);

        $this->rahim = Customer::create(['name' => 'Rahim Uddin', 'mobile' => '01711000001', 'status' => true, 'opening_balance' => 0]);
        $this->karim = Customer::create(['name' => 'Karim Mia', 'mobile' => '01811000002', 'status' => true, 'opening_balance' => 300]);
        $this->paidUp = Customer::create(['name' => 'Paid Up', 'mobile' => '01911000003', 'status' => true, 'opening_balance' => 0]);

        $this->postSale($product, 2, ['paid_amount' => 500], $this->rahim)->assertSessionHasNoErrors();  // due 1500
        $this->postSale($product, 1, ['paid_amount' => 200], $this->rahim)->assertSessionHasNoErrors();  // due 800
        $this->postSale($product, 1, ['paid_amount' => 400], $this->karim)->assertSessionHasNoErrors();  // due 600, + opening 300
        $this->postSale($product, 1, ['paid_amount' => 1000], $this->paidUp)->assertSessionHasNoErrors(); // nothing due

        // Karim pays 100 without choosing an invoice.
        $this->post(route('customer-payments.store'), [
            'customer_id' => $this->karim->unique_id, 'payment_date' => now()->toDateString(), 'amount' => 100, 'method' => 'cash',
        ])->assertSessionHasNoErrors();
    }

    public function test_customer_wise_totals_match_each_balance()
    {
        $rows = app(DueService::class)->partyWise('customers', [], false)->keyBy('name');

        $this->assertSame(['Rahim Uddin', 'Karim Mia'], $rows->keys()->all(), 'Largest due first; fully paid customers are left out.');
        $this->assertEquals(2300, $rows['Rahim Uddin']->balance);
        $this->assertEquals(2, $rows['Rahim Uddin']->due_invoices);
        $this->assertEquals(0, $rows['Rahim Uddin']->other);
        // 600 on the invoice + 300 opening - 100 unlinked payment.
        $this->assertEquals(600, $rows['Karim Mia']->invoice_due);
        $this->assertEquals(200, $rows['Karim Mia']->other);
        $this->assertEquals(800, $rows['Karim Mia']->balance);
        $this->assertEquals($this->karim->fresh()->balance, $rows['Karim Mia']->balance);

        $page = $this->actingAs($this->admin())->get(route('reports.due-list'))->assertOk();
        $page->assertSee('Rahim Uddin')->assertSee('01711000001')->assertSee('3,100.00')->assertDontSee('Paid Up');
    }

    public function test_invoice_wise_lists_only_invoices_with_a_due()
    {
        $this->actingAs($this->admin());
        $page = $this->get(route('reports.due-list', ['view' => 'invoice']))->assertOk();
        foreach (Sale::where('due_amount', '>', 0)->get() as $sale) {
            $page->assertSee($sale->reference_no);
        }
        $page->assertDontSee(Sale::where('customer_id', $this->paidUp->id)->first()->reference_no)->assertSee('2,900.00');

        $this->get(route('reports.due-list', ['view' => 'invoice', 'party_id' => $this->karim->unique_id]))
            ->assertSee('Karim Mia')->assertDontSee('Rahim Uddin');
        $this->get(route('reports.due-list', ['view' => 'invoice', 'from' => now()->addDay()->toDateString()]))
            ->assertSee('No dues. Everything is paid.');
    }

    public function test_statement_groups_invoices_with_a_total_due_per_customer()
    {
        $statement = app(DueService::class)->statement('customers', [])->keyBy('name');

        $this->assertCount(2, $statement['Rahim Uddin']->invoices);
        $this->assertEquals(2300, $statement['Rahim Uddin']->total_due);
        $this->assertEquals(200, $statement['Karim Mia']->other);
        $this->assertEquals(800, $statement['Karim Mia']->total_due);

        // With dates, only the listed invoices count.
        $dated = app(DueService::class)->statement('customers', ['from' => now()->toDateString()])->keyBy('name');
        $this->assertEquals(600, $dated['Karim Mia']->total_due);
    }

    public function test_pdfs_for_both_views_and_one_customer()
    {
        $this->actingAs($this->admin());
        foreach ([[], ['view' => 'invoice'], ['view' => 'invoice', 'party_id' => $this->rahim->unique_id], ['type' => 'suppliers'], ['view' => 'invoice', 'download' => 1]] as $query) {
            $response = $this->get(route('reports.due-list.pdf', $query))->assertOk();
            $this->assertStringStartsWith('%PDF', $response->getContent(), json_encode($query));
            $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        }
        $this->assertStringStartsWith('attachment', $this->get(route('reports.due-list.pdf', ['download' => 1]))->headers->get('Content-Disposition'));
    }

    public function test_supplier_dues()
    {
        $product = $this->product(['purchase_price' => 200, 'opening_stock' => 0]);
        $supplier = $this->supplier();
        $this->postPurchase($product, 10, ['paid_amount' => 500], $supplier)->assertSessionHasNoErrors();

        $rows = app(DueService::class)->partyWise('suppliers', [], false);
        $this->assertEquals(1500, $rows->firstWhere('id', $supplier->id)->balance);
        $this->get(route('reports.due-list', ['type' => 'suppliers', 'view' => 'invoice']))->assertOk()->assertSee('1,500.00');
    }

    public function test_access_follows_the_menu_permission()
    {
        $this->actingAs($this->staff(['dashboard' => ['view']]))->get(route('reports.due-list'))->assertForbidden();
        $this->get(route('reports.due-list.pdf'))->assertForbidden();

        $staff = $this->staff(['dashboard' => ['view'], 'report-due-list' => ['view']]);
        $this->actingAs($staff)->get(route('reports.due-list'))->assertOk()->assertSee('Rahim Uddin');
        $this->get(route('dashboard'))->assertSee(route('reports.due-list'), false);
    }

    public function test_lookup_searches_name_and_mobile()
    {
        $this->actingAs($this->admin());
        $this->getJson(route('lookup', ['type' => 'customers', 'q' => '018110']))
            ->assertOk()->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.id', $this->karim->unique_id)
            ->assertJsonPath('results.0.text', 'Karim Mia (01811000002)')
            ->assertJsonPath('results.0.sub', '01811000002');
        $this->getJson(route('lookup', ['type' => 'customers', 'q' => 'rahim']))->assertJsonPath('results.0.name', 'Rahim Uddin');
        $this->getJson(route('lookup', ['type' => 'products', 'q' => 'T-shirt']))->assertOk()->assertJsonPath('pagination.more', false);
        $this->getJson(route('lookup', ['type' => 'users']))->assertNotFound();

        // The chosen option shows its name and mobile when the page loads.
        $this->get(route('reports.due-list', ['view' => 'invoice', 'party_id' => $this->karim->unique_id]))
            ->assertSee('<option value="' . $this->karim->unique_id . '" selected>Karim Mia (01811000002)</option>', false);

        $this->actingAs($this->staff(['dashboard' => ['view']]))->getJson(route('lookup', ['type' => 'customers']))->assertForbidden();
        $this->actingAs($this->staff(['sales' => ['view']]))->getJson(route('lookup', ['type' => 'customers']))->assertOk();
        $this->getJson(route('lookup', ['type' => 'suppliers']))->assertForbidden();
    }

    public function test_every_party_and_product_filter_uses_the_search_dropdown()
    {
        $this->actingAs($this->admin());
        foreach ([
            route('sales.index'), route('purchases.index'), route('reports.sales'), route('reports.purchases'),
            route('reports.customer-ledger'), route('reports.supplier-ledger'), route('reports.damage-returns'), route('stock.ledger'),
        ] as $url) {
            $this->get($url)->assertOk()->assertSee('js-remote-select', false)->assertSee('remote-select.js', false);
        }
        $this->get(route('purchases.index'))->assertSee(route('lookup', 'suppliers'), false);
        $this->get(route('sales.index'))->assertSee(route('lookup', 'customers'), false);
        $this->get(route('reports.customer-ledger', ['party_id' => $this->rahim->unique_id]))->assertOk()->assertSee('Rahim Uddin (01711000001)');
    }
}
