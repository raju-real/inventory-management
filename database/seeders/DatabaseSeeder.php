<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Menu;
use App\Models\Setting;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->seedMenus();
        $this->seedSettings();

        User::firstOrCreate(['email' => 'admin@example.com'], [
            'name' => 'Administrator',
            'role' => User::ROLE_ADMIN,
            'status' => true,
            'password' => Hash::make('Admin@12345'),
        ]);

        foreach (['General', 'Electronics', 'Clothing', 'Accessories'] as $name) {
            Category::firstOrCreate(['name' => $name]);
        }

        foreach (['Piece' => 'pcs', 'Box' => 'box', 'Carton' => 'ctn', 'Kg' => 'kg', 'Litre' => 'ltr'] as $name => $short) {
            Unit::firstOrCreate(['name' => $name], ['short_name' => $short]);
        }

        Customer::firstOrCreate(['name' => 'Walk-in Customer'])->forceFill(['is_walk_in' => true])->save();
    }

    private function seedMenus(): void
    {
        $crud = ['view', 'create', 'edit', 'delete'];
        $document = ['view', 'create', 'delete'];
        $viewOnly = ['view'];

        $tree = [
            ['Dashboard', 'dashboard', 'dashboard', 'bi-speedometer2', $viewOnly],
            ['Inventory', 'inventory', null, 'bi-box-seam', null, [
                ['Products', 'products', 'products.index', 'bi-box', $crud],
                ['Categories', 'categories', 'categories.index', 'bi-tags', $crud],
                ['Units', 'units', 'units.index', 'bi-rulers', $crud],
                ['Stock Overview', 'stock', 'stock.index', 'bi-stack', $viewOnly],
                ['Stock Ledger', 'stock-ledger', 'stock.ledger', 'bi-journal-text', $viewOnly],
                ['Stock Adjustments', 'stock-adjustments', 'stock-adjustments.index', 'bi-sliders', $document],
            ]],
            ['Parties', 'parties', null, 'bi-people', null, [
                ['Customers', 'customers', 'customers.index', 'bi-person', $crud],
                ['Suppliers', 'suppliers', 'suppliers.index', 'bi-truck', $crud],
            ]],
            ['Transactions', 'transactions', null, 'bi-arrow-left-right', null, [
                ['Purchases', 'purchases', 'purchases.index', 'bi-bag-plus', $document],
                ['Purchase Returns', 'purchase-returns', 'purchase-returns.index', 'bi-bag-dash', $document],
                ['Sales', 'sales', 'sales.index', 'bi-receipt', $document],
                ['Sales Returns', 'sales-returns', 'sales-returns.index', 'bi-arrow-return-left', $document],
                ['Damage Management', 'damages', 'damages.index', 'bi-exclamation-triangle', $document],
                ['Customer Payments', 'customer-payments', 'customer-payments.index', 'bi-cash-coin', $document],
                ['Supplier Payments', 'supplier-payments', 'supplier-payments.index', 'bi-wallet2', $document],
            ]],
            ['Accounts', 'accounts', null, 'bi-bank', null, [
                ['Bank Accounts', 'bank-accounts', 'bank-accounts.index', 'bi-bank2', $crud],
                ['Expense Categories', 'expense-categories', 'expense-categories.index', 'bi-tags', $crud],
                ['Expenses', 'expenses', 'expenses.index', 'bi-arrow-up-right-circle', $crud],
                ['Income Categories', 'income-categories', 'income-categories.index', 'bi-bookmarks', $crud],
                ['Incomes', 'incomes', 'incomes.index', 'bi-arrow-down-left-circle', $crud],
            ]],
            ['Reports', 'reports', null, 'bi-bar-chart', null, [
                ['Stock Report', 'report-stock', 'reports.stock', 'bi-clipboard-data', $viewOnly],
                ['Low Stock Report', 'report-low-stock', 'reports.low-stock', 'bi-bell', $viewOnly],
                ['Purchase Report', 'report-purchases', 'reports.purchases', 'bi-graph-down', $viewOnly],
                ['Sales Report', 'report-sales', 'reports.sales', 'bi-graph-up', $viewOnly],
                ['Damage & Return Report', 'report-damage-returns', 'reports.damage-returns', 'bi-file-earmark-x', $viewOnly],
                ['Customer Ledger', 'report-customer-ledger', 'reports.customer-ledger', 'bi-journal-check', $viewOnly],
                ['Supplier Ledger', 'report-supplier-ledger', 'reports.supplier-ledger', 'bi-journal-arrow-down', $viewOnly],
                ['Due List', 'report-due-list', 'reports.due-list', 'bi-hourglass-split', $viewOnly],
            ]],
            ['Settings', 'settings', null, 'bi-gear', null, [
                ['Users', 'users', 'users.index', 'bi-person-gear', null, [], true],
                ['Menu Manager', 'menus', 'menus.index', 'bi-menu-button-wide', null, [], true],
                ['Profile', 'profile', 'profile.edit', 'bi-person-circle', null, [], false, true],
                ['System Settings', 'system-settings', 'settings.edit', 'bi-sliders2', null, [], true],
                ['Barcode Settings', 'barcode-settings', 'barcode.settings', 'bi-upc-scan', null, [], true],
            ]],
        ];

        foreach ($tree as $order => $item) {
            $parent = $this->seedMenu($item, $order + 1);
            foreach ($item[5] ?? [] as $childOrder => $child) {
                $this->seedMenu($child, $childOrder + 1, $parent->id);
            }
        }
    }

    private function seedMenu(array $item, int $order, ?int $parentId = null): Menu
    {
        return Menu::firstOrCreate(['key' => $item[1]], [
            'parent_id' => $parentId,
            'name' => $item[0],
            'route' => $item[2],
            'icon' => $item[3],
            'actions' => $item[4],
            'admin_only' => $item[6] ?? false,
            'is_public' => $item[7] ?? false,
            'sort_order' => $order,
        ]);
    }

    private function seedSettings(): void
    {
        $defaults = [
            'site_name' => 'Inventory Management',
            'site_email' => null,
            'site_phone' => null,
            'site_address' => null,
            'site_website' => null,
            'site_logo' => null,
            'site_favicon' => null,
            'currency_symbol' => "\u{09F3}",
            'invoice_footer' => 'Thank you for your business.',
            'receipt_width' => '80',
            'points_earn_enabled' => '1',
            'points_earn_amount' => '100',
            'points_redeem_enabled' => '1',
            'points_value' => '0.70',
            'vat_default_mode' => 'product',
            'vat_default_type' => 'percentage',
            'vat_default_value' => '0',
            'barcode_enabled' => '1',
            'barcode_min_length' => '4',
            'barcode_max_interval' => '50',
            'barcode_suffix' => 'enter',
            'barcode_beep' => '1',
            'mail_host' => null,
            'mail_port' => '587',
            'mail_username' => null,
            'mail_password' => null,
            'mail_encryption' => 'tls',
            'mail_from_address' => null,
            'mail_from_name' => null,
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
