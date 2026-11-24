<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UniqueIdTest extends TestCase
{
    /** Tables owned by Laravel itself, which the application never reads or shows. */
    private const FRAMEWORK_TABLES = ['migrations', 'password_resets', 'failed_jobs', 'personal_access_tokens', 'sqlite_sequence'];

    private function appTables(): array
    {
        $names = collect(DB::select("select name from sqlite_master where type = 'table'"))->pluck('name')->all();

        return array_values(array_diff($names, self::FRAMEWORK_TABLES));
    }

    public function test_every_application_table_has_a_unique_id_column()
    {
        $tables = $this->appTables();
        $this->assertGreaterThan(25, count($tables));

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasColumn($table, 'unique_id'), "{$table} has no unique_id");
        }
    }

    public function test_every_row_gets_ten_lowercase_letters_and_they_never_repeat()
    {
        $product = $this->product(['opening_stock' => 10]);
        $this->postSale($product, 2, ['paid_amount' => 10]);
        $sale = Sale::first();
        $this->post(route('sales-returns.store'), [
            'sale_id' => $sale->unique_id, 'return_date' => now()->toDateString(), 'reason' => 'x',
            'items' => [['sale_detail_id' => $sale->details[0]->unique_id, 'quantity' => 1, 'condition' => 'good']],
        ])->assertSessionHasNoErrors();

        $seen = 0;
        foreach ($this->appTables() as $table) {
            $values = DB::table($table)->pluck('unique_id');
            foreach ($values as $value) {
                $this->assertMatchesRegularExpression('/^[a-z]{10}$/', $value, "Bad unique_id in {$table}");
                $seen++;
            }
            $this->assertSame($values->count(), $values->unique()->count(), "Repeated unique_id in {$table}");
        }
        $this->assertGreaterThan(50, $seen);
    }

    public function test_generated_ids_are_all_letters_across_many_records()
    {
        foreach (range(1, 300) as $i) {
            $this->assertMatchesRegularExpression('/^[a-z]{10}$/', Customer::generateUniqueId());
        }
    }

    public function test_pages_open_by_unique_id_and_numeric_urls_do_not_exist()
    {
        $product = $this->product();
        $customer = $this->customer();
        $this->postSale($product, 1, [], $customer);
        $sale = Sale::first();

        $this->assertStringContainsString('/' . $product->unique_id, route('products.show', $product));
        $this->get('products/' . $product->unique_id)->assertOk();
        $this->get('products/' . $product->id)->assertNotFound();
        $this->get('products/' . $product->id . '/edit')->assertNotFound();
        $this->get('customers/' . $customer->id)->assertNotFound();
        $this->get('sales/' . $sale->id)->assertNotFound();
        $this->get('sales/' . $sale->id . '/pdf')->assertNotFound();
        $this->get('users/' . $this->admin()->id . '/edit')->assertNotFound();
        $this->post('sales/' . $sale->id . '/cancel', ['cancel_reason' => 'x'])->assertNotFound();
        $this->delete('products/' . $product->id)->assertNotFound();
    }

    public function test_forms_reject_numeric_ids()
    {
        $product = $this->product();
        $customer = $this->customer();
        $today = now()->toDateString();

        $this->post(route('sales.store'), ['sale_date' => $today, 'customer_id' => $customer->id, 'items' => [['product_id' => $product->unique_id, 'quantity' => 1, 'unit_price' => 1]]])
            ->assertSessionHasErrors('customer_id');
        $this->post(route('sales.store'), ['sale_date' => $today, 'customer_id' => $customer->unique_id, 'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 1]]])
            ->assertSessionHasErrors('items.0.product_id');
        $this->post(route('stock-adjustments.store'), ['adjustment_date' => $today, 'product_id' => $product->id, 'type' => 'increase', 'quantity' => 1, 'reason' => 'x'])
            ->assertSessionHasErrors('product_id');
        $this->assertSame(0, Sale::count());
    }

    public function test_filters_work_by_unique_id_and_ignore_numeric_values()
    {
        $product = $this->product();
        $first = $this->customer();
        $second = Customer::create(['name' => 'Second Buyer', 'status' => true]);
        $this->postSale($product, 1, [], $first);
        $this->postSale($product, 1, [], $second);

        $this->get(route('sales.index', ['party_id' => $second->unique_id]))->assertOk()->assertSee('SAL-000002')->assertDontSee('SAL-000001');
        $this->get(route('sales.index', ['party_id' => $second->id]))->assertOk()->assertDontSee('SAL-000001')->assertDontSee('SAL-000002');
        $this->get(route('reports.customer-ledger', ['party_id' => $second->id]))->assertNotFound();
        $this->get(route('reports.customer-ledger', ['party_id' => $second->unique_id]))->assertOk()->assertSee('Second Buyer');
    }

    public function test_json_responses_hold_no_numeric_id()
    {
        $product = $this->product(['barcode' => '123456789']);
        $this->actingAs($this->admin());

        $this->getJson(route('products.lookup', ['code' => '123456789', 'context' => 'sale']))->assertJson(['id' => $product->unique_id]);
        $this->getJson(route('products.search', ['q' => $product->name, 'context' => 'sale']))->assertJsonPath('0.id', $product->unique_id);
        $this->postJson(route('customers.store'), ['mobile' => '01700000001', 'name' => 'Buyer', 'status' => 1])->assertCreated()->assertJson(['id' => Customer::where('name', 'Buyer')->value('unique_id')]);
    }

    public function test_pages_contain_no_numeric_record_ids()
    {
        $product = $this->product();
        $customer = $this->customer();
        $supplier = $this->supplier();
        $this->postSale($product, 1, ['paid_amount' => 10], $customer);
        $this->postPurchase($product, 1, [], $supplier);
        $sale = Sale::first();
        $purchase = \App\Models\Purchase::first();

        $pages = [
            route('products.index'), route('products.show', $product), route('products.edit', $product), route('products.create'),
            route('customers.show', $customer), route('suppliers.show', $supplier), route('sales.create'), route('purchases.create'),
            route('sales.index'), route('sales.show', $sale), route('purchases.show', $purchase),
            route('sales-returns.create', ['invoice' => $sale->reference_no]), route('purchase-returns.create', ['invoice' => $purchase->reference_no]),
            route('customer-payments.create', ['sale_id' => $sale->unique_id]), route('supplier-payments.create', ['purchase_id' => $purchase->unique_id]),
            route('customer-payments.index'), route('reports.sales'), route('reports.customer-ledger', ['party_id' => $customer->unique_id]), route('stock.ledger'),
            route('users.index'), route('users.access', $this->staff()), route('menus.index'), route('menus.edit', \App\Models\Menu::first()),
        ];

        foreach ($pages as $url) {
            $html = $this->actingAs($this->admin())->get($url)->assertOk()->getContent();

            preg_match_all('/(?:href|action)="([^"]+)"/', $html, $links);
            foreach ($links[1] as $link) {
                $path = parse_url(html_entity_decode($link), PHP_URL_PATH) ?? '';
                $this->assertDoesNotMatchRegularExpression('#/\d+(/|$)#', $path, "Numeric id in link {$link} on {$url}");
            }

            $this->assertDoesNotMatchRegularExpression('/name="[^"]*_id\]?"[^>]*value="\d+"/', $html, "Numeric id in a form field on {$url}");
            $this->assertDoesNotMatchRegularExpression('/name="permissions\[\d+\]/', $html, "Numeric id in permissions on {$url}");
            $this->assertDoesNotMatchRegularExpression('/(?:id|data-bs-target)="#?menu-\d+"/', $html, "Numeric menu id on {$url}");

            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            foreach ($dom->getElementsByTagName('select') as $select) {
                if (! str_ends_with($select->getAttribute('name'), '_id')) {
                    continue;
                }
                foreach ($select->getElementsByTagName('option') as $option) {
                    $this->assertDoesNotMatchRegularExpression('/^\d+$/', $option->getAttribute('value'), "Numeric option in {$select->getAttribute('name')} on {$url}");
                }
            }
        }
    }

    public function test_saved_choices_show_as_selected_in_their_dropdowns()
    {
        $this->actingAs($this->admin());
        $walkIn = Customer::where('name', 'Walk-in Customer')->first();
        $selected = fn ($id) => '/<option value="' . $id . '"\s+selected/';

        $this->assertMatchesRegularExpression($selected($walkIn->unique_id), $this->get(route('sales.create'))->getContent());

        $product = $this->product();
        $edit = $this->get(route('products.edit', $product))->getContent();
        $this->assertMatchesRegularExpression($selected($product->category->unique_id), $edit);
        $this->assertMatchesRegularExpression($selected($product->unit->unique_id), $edit);

        $customer = $this->customer();
        $this->postSale($product, 1, [], $customer);
        $payment = $this->get(route('customer-payments.create', ['sale_id' => Sale::first()->unique_id]))->getContent();
        $this->assertMatchesRegularExpression($selected($customer->unique_id), $payment);

        $filtered = $this->get(route('sales.index', ['party_id' => $customer->unique_id]))->getContent();
        $this->assertMatchesRegularExpression($selected($customer->unique_id), $filtered);

        $menu = \App\Models\Menu::whereNotNull('parent_id')->first();
        $this->assertMatchesRegularExpression($selected($menu->parent->unique_id), $this->get(route('menus.edit', $menu))->getContent());
    }

    public function test_the_login_session_still_works_with_unique_ids()
    {
        $user = $this->staff(['products' => ['view']], ['password' => bcrypt('secret-123')]);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'secret-123'])->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
        $this->get(route('products.index'))->assertOk();
    }
}
