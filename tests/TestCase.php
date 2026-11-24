<?php

namespace Tests;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Menu;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\AccessService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, DatabaseMigrations;

    protected $seed = true;

    protected function admin(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    protected function staff(array $access = [], array $attributes = []): User
    {
        $user = User::create(array_merge([
            'name' => 'Staff', 'email' => 'staff' . uniqid() . '@example.com', 'role' => User::ROLE_USER, 'status' => true, 'password' => bcrypt('secret-123'),
        ], $attributes));

        $permissions = [];
        foreach ($access as $key => $actions) {
            $permissions[Menu::where('key', $key)->firstOrFail()->unique_id] = $actions;
        }
        app(AccessService::class)->sync($user, $permissions);

        return $user;
    }

    protected function product(array $attributes = []): Product
    {
        $response = $this->actingAs($this->admin())->post(route('products.store'), array_merge([
            'name' => 'T-shirt ' . uniqid(),
            'category_id' => Category::first()->unique_id,
            'unit_id' => Unit::first()->unique_id,
            'purchase_price' => 100,
            'sales_price' => 150,
            'opening_stock' => 100,
            'min_stock' => 5,
            'status' => 1,
        ], $attributes));
        $response->assertSessionHasNoErrors();

        return Product::latest('id')->first();
    }

    protected function supplier(): Supplier
    {
        return Supplier::create(['name' => 'ABC Traders', 'email' => 'abc@example.com', 'status' => true, 'opening_balance' => 0]);
    }

    protected function customer(): Customer
    {
        return Customer::create(['name' => 'Rahim', 'email' => 'rahim@example.com', 'status' => true, 'opening_balance' => 0]);
    }

    protected function postSale(Product $product, $quantity, array $extra = [], ?Customer $customer = null)
    {
        return $this->actingAs($this->admin())->post(route('sales.store'), array_merge([
            'sale_date' => now()->toDateString(),
            'customer_id' => ($customer ?? $this->customer())->unique_id,
            'discount_type' => 'percentage',
            'vat_mode' => 'product',
            'items' => [['product_id' => $product->unique_id, 'quantity' => $quantity, 'unit_price' => $product->sales_price]],
        ], $extra));
    }

    protected function postPurchase(Product $product, $quantity, array $extra = [], ?Supplier $supplier = null)
    {
        return $this->actingAs($this->admin())->post(route('purchases.store'), array_merge([
            'purchase_date' => now()->toDateString(),
            'supplier_id' => ($supplier ?? $this->supplier())->unique_id,
            'items' => [['product_id' => $product->unique_id, 'quantity' => $quantity, 'unit_price' => $product->purchase_price]],
        ], $extra));
    }
}
