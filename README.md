# Inventory Management

Laravel 9 inventory system: products, parties, purchases, sales, returns, damage, stock ledger, payments, reports,
PDF invoices, USB barcode scanning, and menu-wise user permissions.

## Run it

Use the XAMPP PHP at `C:\xampp\php\php.exe` (PHP 8.2). The `php` on this machine's PATH is 7.4 and cannot run Laravel 9.

```
C:\xampp\php\php.exe artisan migrate --seed
C:\xampp\php\php.exe artisan storage:link
```

Open `http://localhost/inventory-management/public` and log in with `admin@example.com` / `Admin@12345`.
Change this password straight away under Profile.

## Tests

```
C:\xampp\php\php.exe vendor\bin\phpunit
```

Tests use an in-memory SQLite database, so they never touch real data.

## How it is organised

- **Controllers** handle the request. **Services** in `app/Services` hold the business logic, one per module.
- **Form requests** in `app/Http/Requests` do the backend validation. Forms use the `needs-validation` class for the frontend side.
- **Stock** only ever changes through `StockService`, which writes a `stock_transactions` row and updates `products.current_stock` together.
- **Posted documents** are never edited or deleted. Cancelling writes reversing stock entries and keeps the original.
- **Permissions**: admins can open everything. Other users get menus and actions under Users > Manage Access, stored in `menu_permissions`.
  Protect a controller with `$this->accessResource('menu-key')` and a view with `@access('menu-key', 'create')`.

## Cache

Everything goes through Laravel's `Cache::`, so change `CACHE_DRIVER` in `.env` to file, database, redis or memcached without touching code.
Models use the `HasModelCache` trait, and `ModelCacheObserver` flushes their cache after the database transaction commits.
Cache tags are avoided because the file driver does not support them; `App\Support\VersionedCache` does the same job on every driver.

## Unique IDs

Every application table has a `unique_id`: 10 random lowercase letters, set automatically by the `HasUniqueId` trait.
URLs, form values, filters and JSON use only this value, and numeric IDs are refused. Foreign keys stay numeric inside the database:
a form request lists its fields in `references()`, and `validated()` turns them into internal keys for the services.

Laravel's own tables (`migrations`, `password_resets`, `failed_jobs`, `personal_access_tokens`) are left unchanged, because the app never reads or shows them.

## Accounts

Bank accounts hold a balance that changes only through incomes and expenses, in `BankBalanceService`. Adding, editing or deleting an entry updates the
balance in the same database transaction, and no entry may take a balance below zero. Sales and purchase payments are tracked separately and do not change bank balances.

## Barcode scanner

A USB scanner acts as a keyboard, so it needs no driver. `public/assets/js/scanner.js` recognises a scan by how fast the keys arrive,
wherever the cursor is. Settings and a live test panel are under Settings > Barcode Settings.

## Email

Set the SMTP server under Settings > System Settings > Email (SMTP), then use Send test. Until then, `MAIL_MAILER=log` writes emails to `storage/logs/laravel.log`.

## Demo data

```
C:\xampp\php\php.exe artisan db:seed --class=DemoDataSeeder
```

This adds products with images, parties, invoices, returns, damage, payments and accounts, plus a limited login (`cashier@example.com` / `Cashier@12345`).
It only runs when there are no products yet. The images are simple illustrations in `database/seeders/demo-images`; replace them with real photos.

## Deployment

1. Upload the project and point the domain at the `public` folder, not the project root.
2. Install dependencies: `composer install --no-dev --optimize-autoloader`
3. Copy `.env.example` to `.env`, then run `php artisan key:generate`.
4. In `.env`, set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` and the database details.
5. Start with a clean database: `php artisan migrate --seed --force` (add demo data only if you want it).
6. Run `php artisan storage:link`, and make `storage` and `bootstrap/cache` writable by the web server.
7. Optional for speed: `php artisan config:cache`, `route:cache` and `view:cache`. Repeat `config:cache` after any `.env` change.
   Run `php artisan config:clear` before running tests on a machine with cached config.
8. Log in, change the admin password at once, then fill in System Settings and the SMTP details and use Send test.

To restart from empty on a server: `php artisan migrate:fresh --seed --force`, then `php artisan cache:clear`. This deletes all data.
