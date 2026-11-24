<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\Category;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\IncomeCategory;
use App\Models\Menu;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\AccessService;
use App\Services\BankAccountService;
use App\Services\CustomerService;
use App\Services\DamageService;
use App\Services\ExpenseService;
use App\Services\IncomeService;
use App\Services\PaymentService;
use App\Services\ProductService;
use App\Services\PurchaseReturnService;
use App\Services\PurchaseService;
use App\Services\SaleCalculator;
use App\Services\SaleService;
use App\Services\SalesReturnService;
use App\Services\StockAdjustmentService;
use App\Services\SupplierService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Sample business data for demonstrations: php artisan db:seed --class=DemoDataSeeder
 * It posts through the real services, so stock, balances and ledgers all add up.
 */
class DemoDataSeeder extends Seeder
{
    public function run()
    {
        if (Product::exists()) {
            $this->command?->warn('Products already exist, so the demo data was not added.');

            return;
        }

        auth()->setUser(User::where('role', User::ROLE_ADMIN)->firstOrFail());
        mt_srand(20261105);

        $this->seedSettings();
        $products = $this->seedProducts();
        $customers = $this->seedCustomers();
        $suppliers = $this->seedSuppliers();
        $this->seedPurchases($products, $suppliers);
        $this->seedSales($products, $customers);
        $this->seedStockEvents($products);
        $this->seedAccounts();
        $this->seedCashier();
    }

    private function days(int $ago): string
    {
        return now()->subDays($ago)->toDateString();
    }

    private function seedSettings(): void
    {
        foreach ([
            'site_address' => 'House 12, Road 5, Dhanmondi, Dhaka 1205',
            'site_phone' => '01711000000',
            'site_email' => 'info@example.com',
            'invoice_footer' => 'Thank you for your business. Goods once sold can be returned within 7 days with this invoice.',
        ] as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    private function seedProducts(): array
    {
        $category = fn (string $name) => Category::firstOrCreate(['name' => $name])->id;
        $unit = fn (string $name) => Unit::firstOrCreate(['name' => $name])->id;

        // name, image, category, unit, purchase price, sales price, opening stock, minimum stock
        $rows = [
            ['Wireless Headphones', 'headphones', 'Electronics', 'Piece', 1450, 2200, 12, 5],
            ['Wireless Mouse', 'mouse', 'Electronics', 'Piece', 380, 650, 25, 8],
            ['Mechanical Keyboard', 'keyboard', 'Electronics', 'Piece', 2600, 3800, 8, 4],
            ['Smartphone 128GB', 'phone', 'Electronics', 'Piece', 18500, 22500, 6, 3],
            ['Laptop 14 inch', 'laptop', 'Electronics', 'Piece', 52000, 61500, 4, 2],
            ['Smart Watch', 'smartwatch', 'Electronics', 'Piece', 2100, 3200, 10, 4],
            ['Bluetooth Speaker', 'speaker', 'Electronics', 'Piece', 1250, 1950, 9, 4],
            ['USB Flash Drive 64GB', 'usb-drive', 'Electronics', 'Piece', 420, 700, 40, 10],
            ['Power Bank 20000mAh', 'battery-charging', 'Electronics', 'Piece', 1350, 1990, 14, 5],
            ['Digital Camera', 'camera', 'Electronics', 'Piece', 24500, 29900, 3, 5],
            ['Cotton T-Shirt', 'tshirt', 'Clothing', 'Piece', 240, 450, 80, 20],
            ['Formal Shirt', 'shirt', 'Clothing', 'Piece', 620, 1150, 45, 15],
            ['Denim Jeans', 'jeans', 'Clothing', 'Piece', 950, 1650, 30, 10],
            ['Leather Wallet', 'wallet2', 'Accessories', 'Piece', 480, 850, 22, 6],
            ['Travel Backpack', 'backpack', 'Accessories', 'Piece', 1150, 1890, 15, 5],
            ['Sunglasses', 'sunglasses', 'Accessories', 'Piece', 520, 990, 18, 6],
            ['Insulated Travel Mug', 'cup-hot', 'Accessories', 'Piece', 310, 560, 6, 8],
            ['Folding Umbrella', 'umbrella', 'Accessories', 'Piece', 290, 520, 20, 6],
        ];

        $service = app(ProductService::class);
        $products = [];

        foreach ($rows as $index => [$name, $image, $categoryName, $unitName, $cost, $price, $opening, $minimum]) {
            $product = $service->create([
                'name' => $name,
                'barcode' => $this->ean13('890100000' . str_pad($index + 1, 3, '0', STR_PAD_LEFT)),
                'category_id' => $category($categoryName),
                'unit_id' => $unit($unitName),
                'purchase_price' => $cost,
                'sales_price' => $price,
                'opening_stock' => $opening,
                'min_stock' => $minimum,
                'vat_type' => $categoryName === 'Accessories' ? 'flat' : 'percentage',
                'vat_value' => ['Electronics' => 5, 'Clothing' => 7.5, 'Accessories' => 10][$categoryName],
                'description' => "{$name} for everyday use. Sample product for demonstration.",
                'status' => 1,
            ]);

            $path = "products/{$image}.jpg";
            Storage::disk('public')->put($path, file_get_contents(database_path("seeders/demo-images/{$image}.jpg")));
            $product->update(['image' => $path]);
            $products[] = $product->fresh();
        }

        // The shop has been running for a while, so its opening stock comes first in every history.
        StockTransaction::where('transaction_type', 'opening')->update(['transaction_date' => $this->days(60)]);

        return $products;
    }

    /** A real EAN-13 number, with its check digit, so it prints and scans correctly. */
    private function ean13(string $twelveDigits): string
    {
        $sum = 0;
        foreach (str_split($twelveDigits) as $position => $digit) {
            $sum += (int) $digit * ($position % 2 ? 3 : 1);
        }

        return $twelveDigits . ((10 - $sum % 10) % 10);
    }

    private function seedCustomers(): array
    {
        $service = app(CustomerService::class);
        $walkIn = Customer::firstOrCreate(['name' => 'Walk-in Customer']);
        $walkIn->forceFill(['is_walk_in' => true])->save();
        $customers = [$walkIn];

        foreach ([
            ['Rahim Uddin', '01712345601', 'rahim@example.com', 'Mirpur 10, Dhaka', 0],
            ['Karim Enterprise', '01712345602', 'karim@example.com', 'Agrabad, Chattogram', 2500],
            ['Nusrat Jahan', '01712345603', 'nusrat@example.com', 'Uttara Sector 7, Dhaka', 0],
            ['Sonar Bangla Traders', '01712345604', 'sonarbangla@example.com', 'Zindabazar, Sylhet', 5000],
            ['Tanvir Ahmed', '01712345605', 'tanvir@example.com', 'Banani, Dhaka', 0],
            ['Green Valley School', '01712345606', 'greenvalley@example.com', 'Rajshahi Sadar, Rajshahi', 0],
        ] as [$name, $mobile, $email, $address, $opening]) {
            $customers[] = $service->create(['name' => $name, 'mobile' => $mobile, 'email' => $email, 'address' => $address, 'opening_balance' => $opening, 'status' => 1]);
        }

        return $customers;
    }

    private function seedSuppliers(): array
    {
        $service = app(SupplierService::class);
        $suppliers = [];

        foreach ([
            ['ABC Traders', 'Abdul Karim', '01812345601', 'abc@example.com', 'Elephant Road, Dhaka', 12000],
            ['Global Tech Imports', 'Sadia Rahman', '01812345602', 'globaltech@example.com', 'IDB Bhaban, Agargaon, Dhaka', 0],
            ['Fashion House Ltd', 'Imran Hossain', '01812345603', 'fashionhouse@example.com', 'Gulshan 1, Dhaka', 0],
            ['Daily Needs Wholesale', 'Mithila Akter', '01812345604', 'dailyneeds@example.com', 'Chawkbazar, Dhaka', 3500],
        ] as [$name, $contact, $mobile, $email, $address, $opening]) {
            $suppliers[] = $service->create(['name' => $name, 'contact_person' => $contact, 'mobile' => $mobile, 'email' => $email, 'address' => $address, 'opening_balance' => $opening, 'status' => 1]);
        }

        return $suppliers;
    }

    private function seedPurchases(array $products, array $suppliers): void
    {
        $service = app(PurchaseService::class);
        $byName = collect($products)->keyBy('name');
        $line = fn (string $name, $quantity) => ['product_id' => $byName[$name]->id, 'quantity' => $quantity, 'unit_price' => $byName[$name]->purchase_price];

        // days ago, supplier, lines, discount, other charges, share paid
        foreach ([
            [45, 1, [['Wireless Headphones', 20], ['Wireless Mouse', 40], ['Mechanical Keyboard', 15], ['Smart Watch', 15]], 1500, 800, 1],
            [38, 2, [['Cotton T-Shirt', 120], ['Formal Shirt', 60], ['Denim Jeans', 40]], 2000, 500, 0.6],
            [30, 3, [['Leather Wallet', 30], ['Travel Backpack', 20], ['Sunglasses', 25], ['Folding Umbrella', 25]], 0, 300, 1],
            [22, 1, [['Smartphone 128GB', 10], ['Bluetooth Speaker', 15], ['Power Bank 20000mAh', 20]], 5000, 1200, 0.5],
            [12, 0, [['USB Flash Drive 64GB', 60], ['Laptop 14 inch', 3]], 2500, 600, 0],
        ] as [$ago, $supplier, $lines, $discount, $charges, $share]) {
            $items = array_map(fn ($row) => $line($row[0], $row[1]), $lines);
            $total = collect($items)->sum(fn ($item) => $item['quantity'] * $item['unit_price']) - $discount + $charges;

            $service->create([
                'purchase_date' => $this->days($ago),
                'supplier_id' => $suppliers[$supplier]->id,
                'items' => $items,
                'discount' => $discount,
                'other_charges' => $charges,
                'paid_amount' => round($total * $share, 2),
                'payment_method' => $share === 1 ? 'bank' : 'cash',
                'notes' => $charges > 500 ? 'Includes transport charge.' : null,
            ]);
        }

        // Part of a later payment against the unpaid invoice.
        $unpaid = Purchase::where('paid_amount', 0)->first();
        app(PaymentService::class)->createSupplierPayment([
            'supplier_id' => $unpaid->supplier_id, 'purchase_id' => $unpaid->id, 'payment_date' => $this->days(5),
            'amount' => round((float) $unpaid->due_amount / 2, 2), 'method' => 'bank', 'transaction_ref' => 'TRX-55012',
        ]);
    }

    private function seedSales(array $products, array $customers): void
    {
        $service = app(SaleService::class);
        $sellable = array_values(array_filter($products, fn ($product) => ! in_array($product->name, ['Digital Camera', 'Insulated Travel Mug'], true)));

        // Oldest first, with the last few today so the dashboard has something to show.
        $days = [29, 27, 26, 24, 22, 21, 19, 18, 16, 15, 13, 12, 10, 9, 8, 6, 5, 4, 3, 2, 1, 0, 0, 0];

        foreach ($days as $index => $ago) {
            $picked = [];
            foreach ((array) array_rand($sellable, mt_rand(1, 3)) as $key) {
                $picked[] = $sellable[$key];
            }

            $items = [];
            foreach ($picked as $product) {
                $quantity = mt_rand(1, (float) $product->sales_price > 10000 ? 1 : 3);
                $items[] = ['product_id' => $product->id, 'quantity' => $quantity, 'unit_price' => $product->sales_price, 'vat_type' => $product->vat_type, 'vat_value' => $product->vat_value];
            }

            $customer = $customers[$index % count($customers)];
            $sale = [
                'items' => $items,
                'discount_type' => 'percentage',
                'discount_value' => $index % 4 === 0 ? 5 : 0,
                'vat_mode' => $index % 6 === 5 ? 'order' : 'product',
                'vat_type' => 'percentage',
                'vat_value' => 5,
            ];
            $total = app(SaleCalculator::class)->calculate($sale)['grand_total'];
            $paid = $customer->is_walk_in || $index % 3 === 0 ? $total : ($index % 3 === 1 ? round($total * 0.5) : 0);

            $service->create($sale + [
                'sale_date' => $this->days($ago),
                'customer_id' => $customer->id,
                'paid_amount' => $paid,
                'payment_method' => ['cash', 'mobile', 'card', 'bank'][$index % 4],
            ]);
        }

        // Some customers later settle what they owe.
        $payments = app(PaymentService::class);
        foreach (Sale::where('due_amount', '>', 0)->oldest('id')->limit(4)->get() as $sale) {
            $payments->createCustomerPayment([
                'customer_id' => $sale->customer_id, 'sale_id' => $sale->id, 'payment_date' => $this->days(1),
                'amount' => $sale->due_amount, 'method' => 'mobile', 'transaction_ref' => 'BK' . mt_rand(100000, 999999),
            ]);
        }

        // A loyal customer uses some of their points.
        $loyal = Customer::where('name', 'Rahim Uddin')->first();
        $mouse = collect($sellable)->firstWhere('name', 'Wireless Mouse');
        $useSale = ['items' => [['product_id' => $mouse->id, 'quantity' => 1, 'unit_price' => $mouse->sales_price, 'vat_type' => $mouse->vat_type, 'vat_value' => $mouse->vat_value]], 'discount_type' => 'percentage', 'discount_value' => 0, 'vat_mode' => 'product'];
        $pointsToUse = min(20, (int) $loyal->fresh()->points);
        $toPay = app(SaleCalculator::class)->calculate($useSale)['grand_total'] - round($pointsToUse * 0.70, 2);
        $service->create($useSale + ['sale_date' => $this->days(0), 'customer_id' => $loyal->id, 'points_redeemed' => $pointsToUse, 'paid_amount' => $toPay, 'payment_method' => 'cash']);

        // One customer payment on account, with no invoice attached.
        $payments->createCustomerPayment([
            'customer_id' => Customer::where('name', 'Karim Enterprise')->value('id'), 'payment_date' => $this->days(3),
            'amount' => 1500, 'method' => 'cash', 'notes' => 'Part of the opening balance.',
        ]);
    }

    private function seedStockEvents(array $products): void
    {
        $byName = collect($products)->keyBy('name');
        $sales = Sale::with('details')->oldest('id')->get();

        // A customer return in good condition, and one received damaged.
        $returns = app(SalesReturnService::class);
        $good = $sales[4];
        $returns->create([
            'sale_id' => $good->id, 'return_date' => $this->days(14), 'reason' => 'Wrong size', 'refund_amount' => 0,
            'items' => [['sale_detail_id' => $good->details[0]->id, 'quantity' => 1, 'condition' => 'good']],
        ]);
        $damaged = $sales[9];
        $returns->create([
            'sale_id' => $damaged->id, 'return_date' => $this->days(7), 'reason' => 'Arrived broken', 'notes' => 'Refund paid in cash.',
            'refund_amount' => round(((float) $damaged->details[0]->unit_price * $damaged->netRatio() + $damaged->details[0]->vat_per_unit) / 2),
            'items' => [['sale_detail_id' => $damaged->details[0]->id, 'quantity' => 1, 'condition' => 'damaged']],
        ]);

        // A return to the supplier.
        $purchase = Purchase::with('details')->oldest('id')->skip(1)->first();
        app(PurchaseReturnService::class)->create([
            'purchase_id' => $purchase->id, 'return_date' => $this->days(20), 'reason' => 'Stitching defect', 'refund_amount' => 1000,
            'items' => [['purchase_detail_id' => $purchase->details[0]->id, 'quantity' => 5]],
        ]);

        // Damage entries.
        $damages = app(DamageService::class);
        $damages->create(['damage_date' => $this->days(17), 'notes' => 'Water leak in the store room.', 'items' => [
            ['product_id' => $byName['Cotton T-Shirt']->id, 'quantity' => 4, 'reason' => 'Water damage'],
            ['product_id' => $byName['Formal Shirt']->id, 'quantity' => 2, 'reason' => 'Water damage'],
        ]]);
        $damages->create(['damage_date' => $this->days(6), 'items' => [
            ['product_id' => $byName['Bluetooth Speaker']->id, 'quantity' => 1, 'reason' => 'Dropped during handling'],
        ]]);

        // Stock count corrections.
        $adjustments = app(StockAdjustmentService::class);
        $adjustments->create(['adjustment_date' => $this->days(11), 'product_id' => $byName['Leather Wallet']->id, 'type' => 'increase', 'quantity' => 2, 'reason' => 'Found during stock count']);
        $adjustments->create(['adjustment_date' => $this->days(4), 'product_id' => $byName['Folding Umbrella']->id, 'type' => 'decrease', 'quantity' => 1, 'reason' => 'Missing at stock count']);

        // A cancelled sale, so the history shows how corrections look.
        app(SaleService::class)->cancel($sales[17], 'Customer changed their mind');
    }

    private function seedAccounts(): void
    {
        $banks = app(BankAccountService::class);
        $main = $banks->create(['bank_name' => 'Dutch-Bangla Bank', 'branch' => 'Dhanmondi Branch', 'account_number' => '1011 2200 3345', 'opening_balance' => 450000, 'status' => 1]);
        $second = $banks->create(['bank_name' => 'BRAC Bank', 'branch' => 'Gulshan Branch', 'account_number' => '1501 2044 7788', 'opening_balance' => 275000, 'status' => 1]);
        $cash = $banks->create(['bank_name' => 'Cash in Hand', 'branch' => 'Head Office', 'account_number' => 'CASH-001', 'opening_balance' => 60000, 'status' => 1]);

        $expense = fn (string $name, string $description) => ExpenseCategory::firstOrCreate(['name' => $name], ['description' => $description]);
        $rent = $expense('Office Rent', 'Monthly rent for shop and office');
        $utilities = $expense('Utilities', 'Electricity, internet and water');
        $salaries = $expense('Salaries', 'Staff salaries');
        $transport = $expense('Transport', 'Delivery and travel costs');
        $marketing = $expense('Marketing', 'Advertising and promotion');

        $income = fn (string $name, string $description) => IncomeCategory::firstOrCreate(['name' => $name], ['description' => $description]);
        $service = $income('Service Charge', 'Repair and installation services');
        $commission = $income('Commission', 'Commission from partners');
        $interest = $income('Bank Interest', 'Interest earned on deposits');

        $expenses = app(ExpenseService::class);
        foreach ([
            [28, $rent, $main, 45000, 'CHQ-1041', 'Shop rent'],
            [27, $salaries, $main, 85000, 'SAL-OCT', 'Staff salaries'],
            [24, $utilities, $second, 6800, 'DESCO-221', 'Electricity bill'],
            [20, $transport, $cash, 3200, null, 'Delivery to Chattogram'],
            [16, $marketing, $second, 15000, 'FB-ADS-12', 'Online advertising'],
            [12, $transport, $cash, 1850, null, 'Local deliveries'],
            [8, $utilities, $second, 2400, 'ISP-908', 'Internet bill'],
            [3, $marketing, $cash, 4500, null, 'Printed flyers'],
            [1, $transport, $cash, 950, null, 'Courier charges'],
        ] as [$ago, $category, $bank, $amount, $reference, $notes]) {
            $expenses->create(['entry_date' => $this->days($ago), 'category_id' => $category->id, 'bank_account_id' => $bank->id, 'amount' => $amount, 'reference' => $reference, 'notes' => $notes]);
        }

        $incomes = app(IncomeService::class);
        foreach ([
            [25, $service, $main, 18500, 'SRV-301', 'Laptop servicing contract'],
            [18, $commission, $second, 12000, 'COM-77', 'Partner commission'],
            [14, $service, $cash, 3500, 'SRV-302', 'Phone repairs'],
            [9, $interest, $main, 2150, null, 'Quarterly interest'],
            [2, $service, $cash, 4200, 'SRV-303', 'Installation service'],
        ] as [$ago, $category, $bank, $amount, $reference, $notes]) {
            $incomes->create(['entry_date' => $this->days($ago), 'category_id' => $category->id, 'bank_account_id' => $bank->id, 'amount' => $amount, 'reference' => $reference, 'notes' => $notes]);
        }
    }

    /** A second login, to show what a user with limited menus sees. */
    private function seedCashier(): void
    {
        $cashier = User::create([
            'name' => 'Sales Cashier', 'email' => 'cashier@example.com', 'phone' => '01711000001', 'role' => User::ROLE_USER, 'status' => true,
            'password' => Hash::make('Cashier@12345'),
        ]);

        $permissions = [];
        foreach ([
            'dashboard' => ['view'], 'products' => ['view'], 'customers' => ['view', 'create'], 'sales' => ['view', 'create'],
            'sales-returns' => ['view', 'create'], 'customer-payments' => ['view', 'create'],
        ] as $key => $actions) {
            $permissions[Menu::where('key', $key)->value('unique_id')] = $actions;
        }

        app(AccessService::class)->sync($cashier, $permissions);
    }
}
