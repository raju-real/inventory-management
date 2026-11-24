<?php

namespace App\Providers;

use App\Models;
use App\Observers\ModelCacheObserver;
use App\Services\AccessService;
use App\Services\DashboardService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        Paginator::useBootstrapFive();

        foreach ([
            Models\User::class, Models\Setting::class, Models\Menu::class, Models\MenuPermission::class,
            Models\Category::class, Models\Unit::class, Models\Product::class, Models\Customer::class, Models\Supplier::class,
            Models\Purchase::class, Models\Sale::class, Models\SalesReturn::class, Models\PurchaseReturn::class, Models\Damage::class,
            Models\StockAdjustment::class, Models\StockTransaction::class, Models\CustomerPayment::class, Models\SupplierPayment::class,
            Models\BankAccount::class, Models\ExpenseCategory::class, Models\IncomeCategory::class, Models\Expense::class, Models\Income::class, Models\PointTransaction::class,
        ] as $model) {
            $model::observe(ModelCacheObserver::class);
        }

        Blade::if('access', fn (string $menuKey, string $action = 'view') => auth()->check() && auth()->user()->canAccess($menuKey, $action));

        View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            $view->with([
                'sidebarMenus' => app(AccessService::class)->sidebarFor($user),
                'lowStockCount' => $user->canAccess('report-low-stock') || $user->canAccess('products') ? app(DashboardService::class)->lowStockCount() : 0,
            ]);
        });

        $this->applySettings();
    }

    /** Site name and SMTP details are managed from System Settings, not from .env */
    private function applySettings(): void
    {
        $name = setting('site_name');
        if ($name) {
            config(['app.name' => $name]);
        }

        if ($this->app->runningUnitTests() || ! setting('mail_host')) {
            return;
        }

        $password = setting('mail_password');
        try {
            $password = $password ? Crypt::decryptString($password) : null;
        } catch (\Throwable $e) {
            $password = null;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => setting('mail_host'),
            'mail.mailers.smtp.port' => (int) setting('mail_port', 587),
            'mail.mailers.smtp.username' => setting('mail_username'),
            'mail.mailers.smtp.password' => $password,
            'mail.mailers.smtp.encryption' => setting('mail_encryption'),
            'mail.from.address' => setting('mail_from_address', config('mail.from.address')),
            'mail.from.name' => setting('mail_from_name', setting('site_name', config('app.name'))),
        ]);
    }
}
