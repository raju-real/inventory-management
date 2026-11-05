<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Damage;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockTransaction;
use App\Support\VersionedCache;
use Illuminate\Support\Facades\DB;

class DashboardService extends BaseService
{
    public function stats(): array
    {
        $today = now()->toDateString();

        return VersionedCache::remember('dashboard', "stats:{$today}", function () use ($today) {
            $sales = Sale::posted()->whereDate('sale_date', $today);
            $purchases = Purchase::posted()->whereDate('purchase_date', $today);

            return [
                'total_products' => Product::active()->count(),
                'available_stock' => (float) Product::active()->sum('current_stock'),
                'today_sales_amount' => (float) (clone $sales)->sum('grand_total'),
                'today_sales_qty' => (float) (clone $sales)->sum('total_quantity'),
                'today_purchases_amount' => (float) (clone $purchases)->sum('grand_total'),
                'today_purchases_qty' => (float) (clone $purchases)->sum('total_quantity'),
                'damaged_items' => $this->damagedQuantity(),
                'low_stock' => Product::lowStock()->count(),
                'low_stock_products' => Product::with('unit')->lowStock()->orderBy('current_stock')->limit(6)->get(),
                'recent' => StockTransaction::with('product')->latest('transaction_date')->latest('id')->limit(10)->get(),
            ];
        });
    }

    public function accounts(): array
    {
        $today = now()->toDateString();

        return VersionedCache::remember('dashboard', "accounts:{$today}", function () {
            $month = [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()];

            return [
                'balance' => (float) BankAccount::where('status', true)->sum('current_balance'),
                'income' => (float) Income::whereBetween('entry_date', $month)->sum('amount'),
                'expense' => (float) Expense::whereBetween('entry_date', $month)->sum('amount'),
            ];
        });
    }

    public function lowStockCount(): int
    {
        return VersionedCache::remember('dashboard', 'low_stock_count', fn () => Product::lowStock()->count());
    }

    private function damagedQuantity(): float
    {
        $recorded = (float) DB::table('damage_details')
            ->join('damages', 'damages.id', '=', 'damage_details.damage_id')
            ->where('damages.status', 'posted')
            ->sum('damage_details.quantity');

        $returned = (float) DB::table('sales_return_details')
            ->join('sales_returns', 'sales_returns.id', '=', 'sales_return_details.sales_return_id')
            ->where('sales_returns.status', 'posted')
            ->where('sales_return_details.condition', 'damaged')
            ->sum('sales_return_details.quantity');

        return $recorded + $returned;
    }
}
