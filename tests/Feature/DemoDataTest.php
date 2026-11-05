<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoDataTest extends TestCase
{
    public function test_demo_data_is_complete_and_every_figure_adds_up()
    {
        Storage::fake('public');
        $this->seed(DemoDataSeeder::class);

        $this->assertSame(18, Product::count());
        $this->assertGreaterThan(5, Customer::count());
        $this->assertSame(4, Supplier::count());
        $this->assertSame(5, Purchase::count());
        $this->assertSame(24, Sale::count());

        foreach (Product::all() as $product) {
            Storage::disk('public')->assertExists($product->image);
            $size = getimagesizefromstring(Storage::disk('public')->get($product->image));
            $this->assertSame([600, 600, 'image/jpeg'], [$size[0], $size[1], $size['mime']], "{$product->name} has a bad image");

            $moved = StockTransaction::where('product_id', $product->id)->sum('quantity_in') - StockTransaction::where('product_id', $product->id)->sum('quantity_out');
            $this->assertEquals((float) $product->current_stock, round((float) $moved, 3), "{$product->name} stock does not match its ledger");
            $this->assertGreaterThanOrEqual(0, (float) $product->current_stock);
            $this->assertMatchesRegularExpression('/^\d{13}$/', $product->barcode);
        }

        foreach (BankAccount::all() as $bank) {
            $computed = (float) $bank->opening_balance + (float) Income::where('bank_account_id', $bank->id)->sum('amount') - (float) Expense::where('bank_account_id', $bank->id)->sum('amount');
            $this->assertEquals((float) $bank->current_balance, round($computed, 2));
        }

        $this->assertGreaterThan(0, Product::lowStock()->count(), 'The low stock alert should have something to show.');
        $this->assertGreaterThan(0, Sale::posted()->whereDate('sale_date', now()->toDateString())->count());
        $this->assertSame(1, Sale::where('status', 'cancelled')->count());

        $this->actingAs($this->admin())->get(route('dashboard'))->assertOk()->assertSee('Today');
    }

    public function test_it_will_not_run_on_top_of_existing_products()
    {
        Storage::fake('public');
        $this->product();

        $this->seed(DemoDataSeeder::class);

        $this->assertSame(1, Product::count());
        $this->assertSame(0, User::where('email', 'cashier@example.com')->count());
    }
}
