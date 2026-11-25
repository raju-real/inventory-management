<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Product;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    private function lowProduct(): Product
    {
        // 10 in stock, minimum 3: selling 7 or more makes it low.
        return $this->product(['sales_price' => 100, 'opening_stock' => 10, 'min_stock' => 3]);
    }

    public function test_a_sale_that_makes_stock_low_raises_one_alert()
    {
        $product = $this->lowProduct();
        $this->assertSame(0, AppNotification::count());

        $this->postSale($product, 5, ['paid_amount' => 500])->assertSessionHasNoErrors(); // 5 left: still fine
        $this->assertSame(0, AppNotification::count());

        $this->postSale($product, 3, ['paid_amount' => 300])->assertSessionHasNoErrors(); // 2 left: low
        $alert = AppNotification::sole();
        $this->assertSame('low_stock', $alert->type);
        $this->assertStringContainsString($product->name, $alert->title);
        $this->assertStringContainsString('Only 2', $alert->message);

        $this->postSale($product, 1, ['paid_amount' => 100]); // 1 left: still low, no second alert
        $this->assertSame(1, AppNotification::count());

        $this->postSale($product, 1, ['paid_amount' => 100]); // 0 left: out of stock
        $this->assertSame(2, AppNotification::count());
        $this->assertNotNull($alert->fresh()->resolved_at, 'The low stock alert is closed when it gets worse.');
        $this->assertSame('out_of_stock', AppNotification::latest('id')->first()->type);
    }

    public function test_restocking_closes_the_alert()
    {
        $product = $this->lowProduct();
        $this->postSale($product, 8, ['paid_amount' => 800]);
        $this->assertNull(AppNotification::sole()->resolved_at);

        $this->postPurchase($product, 20, ['paid_amount' => 0])->assertSessionHasNoErrors();
        $this->assertNotNull(AppNotification::sole()->resolved_at);
        $this->actingAs($this->admin())->get(route('notifications.index'))->assertSee('Restocked');
    }

    public function test_changing_the_minimum_can_raise_an_alert()
    {
        $product = $this->lowProduct();
        $data = ['name' => $product->name, 'category_id' => $product->category->unique_id, 'unit_id' => $product->unit->unique_id,
            'purchase_price' => 50, 'sales_price' => 100, 'min_stock' => 12, 'status' => 1];

        $this->actingAs($this->admin())->put(route('products.update', $product), $data)->assertSessionHasNoErrors();
        $this->assertSame('low_stock', AppNotification::sole()->type);
    }

    public function test_seen_and_unseen_are_kept_per_user()
    {
        $product = $this->lowProduct();
        $this->postSale($product, 8, ['paid_amount' => 800]);
        $alert = AppNotification::sole();
        $admin = $this->admin();
        $keeper = $this->staff(['dashboard' => ['view'], 'products' => ['view']]);

        $this->actingAs($admin)->getJson(route('notifications.feed', ['count_only' => 1]))->assertExactJson(['count' => 1]);
        $this->get(route('dashboard'))->assertSee('notify-count', false);

        // Opening it marks it seen and goes to the product.
        $this->get(route('notifications.open', $alert))->assertRedirect(route('products.show', $product));
        $this->getJson(route('notifications.feed'))->assertJson(['count' => 0])->assertJsonPath('html', fn ($html) => str_contains($html, 'is-read'));

        // The other user still has it unseen.
        $this->actingAs($keeper)->getJson(route('notifications.feed', ['count_only' => 1]))->assertJson(['count' => 1]);

        // Toggle back to unseen and to seen again.
        $this->actingAs($admin)->postJson(route('notifications.toggle', $alert))->assertJson(['read' => false, 'count' => 1]);
        $this->postJson(route('notifications.toggle', $alert))->assertJson(['read' => true, 'count' => 0]);

        $this->actingAs($keeper)->postJson(route('notifications.read-all'))->assertJson(['count' => 0]);
        $this->getJson(route('notifications.feed', ['count_only' => 1]))->assertJson(['count' => 0]);
    }

    public function test_the_page_lists_and_filters()
    {
        $product = $this->lowProduct();
        $this->postSale($product, 8, ['paid_amount' => 800]);
        $alert = AppNotification::sole();

        $this->actingAs($this->admin())->get(route('notifications.index'))->assertOk()->assertSee($alert->title)->assertSee('bi-check2-all', false);
        $this->get(route('notifications.index', ['show' => 'unread']))->assertSee($alert->title);

        $this->post(route('notifications.toggle', $alert))->assertRedirect();
        $this->get(route('notifications.index', ['show' => 'unread']))->assertDontSee($alert->title)->assertSee('You have no unread notifications.');
        $this->get(route('notifications.index'))->assertSee($alert->title)->assertDontSee('bi-check2-all', false);
    }

    public function test_only_users_who_handle_stock_see_alerts()
    {
        $cashier = $this->staff(['dashboard' => ['view'], 'sales' => ['view', 'create']]);

        $this->actingAs($cashier)->get(route('dashboard'))->assertOk()->assertDontSee('notify-count', false);
        $this->get(route('notifications.index'))->assertForbidden();
        $this->getJson(route('notifications.feed'))->assertForbidden();

        $reporter = $this->staff(['dashboard' => ['view'], 'report-low-stock' => ['view']]);
        $product = $this->lowProduct();
        $this->postSale($product, 8, ['paid_amount' => 800]);
        $this->actingAs($reporter)->get(route('notifications.open', AppNotification::sole()))->assertRedirect(route('reports.low-stock'));
    }
}
