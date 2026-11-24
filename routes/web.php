<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerPaymentController;
use App\Http\Controllers\DamageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IncomeCategoryController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SalesReturnController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierPaymentController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
    Route::get('forgot-password', [AuthController::class, 'showForgot'])->name('password.request');
    Route::post('forgot-password', [AuthController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('reset-password/{token}', [AuthController::class, 'showReset'])->name('password.reset');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/', [AuthController::class, 'home'])->name('home');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

    // Inventory
    Route::resource('categories', CategoryController::class)->except('show');
    Route::resource('units', UnitController::class)->except('show');
    Route::get('products-search', [ProductController::class, 'search'])->name('products.search');
    Route::get('products-lookup', [ProductController::class, 'lookup'])->name('products.lookup');
    Route::get('products/{product}/labels', [ProductController::class, 'labels'])->name('products.labels');
    Route::resource('products', ProductController::class);
    Route::get('stock', [StockController::class, 'index'])->name('stock.index');
    Route::get('stock-ledger', [StockController::class, 'ledger'])->name('stock.ledger');
    Route::resource('stock-adjustments', StockAdjustmentController::class)->only('index', 'create', 'store');
    Route::post('stock-adjustments/{stockAdjustment}/cancel', [StockAdjustmentController::class, 'cancel'])->name('stock-adjustments.cancel');

    // Parties
    Route::resource('customers', CustomerController::class);
    Route::resource('suppliers', SupplierController::class);

    // Transactions: posted documents are cancelled, never edited or deleted.
    Route::resource('purchases', PurchaseController::class)->only('index', 'create', 'store', 'show');
    Route::post('purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])->name('purchases.cancel');
    Route::get('purchases/{purchase}/pdf', [PurchaseController::class, 'pdf'])->name('purchases.pdf');
    Route::post('purchases/{purchase}/email', [PurchaseController::class, 'email'])->name('purchases.email');

    Route::resource('sales', SaleController::class)->only('index', 'create', 'store', 'show');
    Route::post('sales/{sale}/cancel', [SaleController::class, 'cancel'])->name('sales.cancel');
    Route::get('sales/{sale}/pdf', [SaleController::class, 'pdf'])->name('sales.pdf');
    Route::get('sales/{sale}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt');
    Route::post('sales/{sale}/email', [SaleController::class, 'email'])->name('sales.email');

    Route::resource('sales-returns', SalesReturnController::class)->only('index', 'create', 'store', 'show');
    Route::post('sales-returns/{salesReturn}/cancel', [SalesReturnController::class, 'cancel'])->name('sales-returns.cancel');

    Route::resource('purchase-returns', PurchaseReturnController::class)->only('index', 'create', 'store', 'show');
    Route::post('purchase-returns/{purchaseReturn}/cancel', [PurchaseReturnController::class, 'cancel'])->name('purchase-returns.cancel');

    Route::resource('damages', DamageController::class)->only('index', 'create', 'store', 'show');
    Route::post('damages/{damage}/cancel', [DamageController::class, 'cancel'])->name('damages.cancel');

    Route::resource('customer-payments', CustomerPaymentController::class)->only('index', 'create', 'store');
    Route::post('customer-payments/{customerPayment}/cancel', [CustomerPaymentController::class, 'cancel'])->name('customer-payments.cancel');

    Route::resource('supplier-payments', SupplierPaymentController::class)->only('index', 'create', 'store');
    Route::post('supplier-payments/{supplierPayment}/cancel', [SupplierPaymentController::class, 'cancel'])->name('supplier-payments.cancel');

    // Accounts
    Route::resource('bank-accounts', BankAccountController::class);
    Route::resource('expense-categories', ExpenseCategoryController::class)->except('show');
    Route::resource('expenses', ExpenseController::class)->except('show');
    Route::resource('income-categories', IncomeCategoryController::class)->except('show');
    Route::resource('incomes', IncomeController::class)->except('show');

    // Reports
    Route::prefix('reports')->name('reports.')->controller(ReportController::class)->group(function () {
        Route::get('stock', 'stock')->name('stock');
        Route::get('low-stock', 'lowStock')->name('low-stock');
        Route::get('purchases', 'purchases')->name('purchases');
        Route::get('sales', 'sales')->name('sales');
        Route::get('damage-returns', 'damageReturns')->name('damage-returns');
        Route::get('customer-ledger', 'customerLedger')->name('customer-ledger');
        Route::get('supplier-ledger', 'supplierLedger')->name('supplier-ledger');
    });

    // Admin only (enforced in the controllers)
    Route::resource('users', UserController::class)->except('show');
    Route::get('users/{user}/access', [UserController::class, 'access'])->name('users.access');
    Route::put('users/{user}/access', [UserController::class, 'updateAccess'])->name('users.access.update');
    Route::resource('menus', MenuController::class)->only('index', 'edit', 'update');
    Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    Route::put('settings/mail', [SettingController::class, 'updateMail'])->name('settings.mail');
    Route::post('settings/mail/test', [SettingController::class, 'testMail'])->name('settings.mail.test');
    Route::get('barcode-settings', [BarcodeController::class, 'settings'])->name('barcode.settings');
    Route::put('barcode-settings', [BarcodeController::class, 'update'])->name('barcode.settings.update');
});
