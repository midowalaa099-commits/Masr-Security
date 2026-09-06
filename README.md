<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="MASR Security — Laravel">
</p>

# MASR Security

Bilingual (English / Arabic, LTR / RTL) e-commerce storefront plus a full admin
panel for MASR Security, an Egyptian Hikvision value-added distributor. Built on
**Laravel 13**, Blade + Alpine.js, **Tailwind CSS v4** (Vite), MySQL, and
**Paymob** for payments.

- Server-side cart (session for guests, database for logged-in customers)
- Server-recomputed checkout totals with transactional, row-locked inventory
- Packages (kits) priced off their component stock with per-kit discounts
- Customer quote-request form and admin quote queue
- Admin: categories, products (images + specs), packages, orders, payments,
  quotes, settings, audit logs
- Paymob Intention API with HMAC-SHA512 callbacks and a fully local **sandbox**
  so the flow runs before real keys are configured

> **Docs:** [Architecture](./docs/architecture.md) ·
> [Payment flow](./docs/payment-flow.md)

## Requirements

- PHP **8.3+** (composer deps tested on PHP 8.4) with the `pdo_mysql` extension
- Composer 2
- Node.js 20+ and npm
- MySQL 8+ (the app is developed against MySQL; tests use in-memory SQLite)

## Local setup

```bash
# 1. Install dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Configure .env (database, app url, admin, Paymob) — see below

# 4. Storage symlink (public product images)
php artisan storage:link

# 5. Database + seed
php artisan migrate --seed

# 6. Build the frontend assets
npm run build        # or: npm run dev   (Vite dev server)

# 7. Serve
php artisan serve
```

Alternatively, Laravel's `composer run dev` starts the dev server + Vite
together. The repository also ships a `setup` script that performs steps
1, 2, 5 and 6 non-interactively (`composer run setup`).

> The storefront runs against the **database** session, cache, and queue
> drivers. The `sessions`, `cache`, `jobs`, `queue` and `cache_locks` tables are
> created by the stock Laravel migrations — no extra service is required.

## Environment variables

The full set lives in `.env.example` with comments. The important groups:

| Variable | Notes |
| --- | --- |
| `APP_NAME`, `APP_URL`, `APP_DEBUG`, `APP_KEY`, `APP_LOCALE` | App basics. `APP_LOCALE` is `en` by default; users can switch to Arabic in the UI |
| `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Primary database (`masr_security`) |
| `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database` | Database-backed session/cache/queue (recommended, matches dev) |
| `FILESYSTEM_DISK=local` | Public product images live on the `local` disk (`storage/app/public`) and are served through `storage:link` |
| `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `ADMIN_PHONE` | Credentials for the bootstrap admin created by `AdminSeeder` (**defaults are for local use only — change them**) |
| `PAYMOB_*` | See [Paymob configuration](#paymob-configuration) below |

`.env` itself is git-ignored; never commit real keys or credentials.

## Database setup

```bash
php artisan migrate          # create the schema
php artisan migrate:fresh    # drop + recreate
```

The `settings` screen is stored in a `settings` table managed by
`SettingsService` — run `SettingsSeeder` (or `php artisan db:seed`) once so the
storefront has company info and a shipping fee.

## Seeding

```bash
php artisan db:seed                     # all seeders
php artisan migrate:fresh --seed        # clean DB + seed everything
```

| Seeder | What it creates |
| --- | --- |
| `SettingsSeeder` | Site settings: company name/phone/email/address, `shipping_fee=60`, WhatsApp, socials, map embed, en/ar hero copy |
| `AdminSeeder` | The admin user from `ADMIN_*` env vars (default `admin@masr-security.com` / `password` — **change before any real use**) |
| `CategorySeeder` | 6 top-level categories (CCTV Cameras, NVR/DVR, Access Control, Interactive Displays, Networking, Storage) + camera type children |
| `ProductSeeder` | 14 demo Hikvision products (skus like `HIK-BUL-4MP`), including component/simple types, featured, on-sale and out-of-stock cases |
| `PackageSeeder` | 2 demo kits (`cctv-kit-4-cameras`, `cctv-kit-8-cameras`) built from component products with a discount |

## Storage

Product/package images are stored on the `local` disk under
`storage/app/public/` and exposed via the `/storage` symlink:

```bash
php artisan storage:link
```

If you store nothing locally and wire up S3 later, set `FILESYSTEM_DISK=s3` and
the `AWS_*` variables (image uploads go through Laravel's `Storage` facade).

## Frontend build

```bash
npm install
npm run build    # production build → public/build
npm run dev      # Vite dev server with hot reload
```

The Tailwind v4 theme (brand palette) is defined in `resources/css/app.css`;
Alpine is loaded through the Vite bundle.

## Testing

```bash
php artisan test            # full suite
php artisan test --compact  # minimal output
vendor/bin/phpunit          # direct runner
```

Tests use `RefreshDatabase` against in-memory SQLite (see `phpunit.xml`), so no
database setup is needed. Coverage includes the storefront pages, cart,
checkout + sandbox payment, admin CRUD/authorization, inventory restore on
cancel, and Paymob HMAC verification.

## Admin panel

Open `/admin` (or `/dashboard` after login). The middleware `EnsureUserIsAdmin`
rejects non-admin users server-side with `403`, and guests are redirected to the
login page.

Default (local) credentials:

```
email:    admin@masr-security.com
password: password
```

Change them via the `ADMIN_*` environment variables **before** running the
seeder in any shared/production environment.

Admin capabilities: dashboard with KPIs (sales, orders, low stock, top
sellers), categories CRUD, products CRUD with image upload/reorder and spec
rows, packages CRUD with an AJAX line-total calculator, order status updates
(setting `cancelled` restores stock automatically), payment ledger, quote queue,
site settings, and the audit log.

## Paymob configuration

The app runs in **sandbox mode out of the box**: when `PAYMOB_SECRET_KEY` /
`PAYMOB_PUBLIC_KEY` are empty (or `PAYMOB_SANDBOX_MODE=true`), checkout redirects
to a local `/payments/sandbox/{payment}` page where you choose
"Simulate Successful Payment" or "Simulate Failed Payment". The sandbox feeds
the chosen outcome through the exact same state-transition code as real
callbacks, so you can exercise the entire order lifecycle without credentials.

To go live:

```
PAYMOB_API_URL=https://accept.paymob.com
PAYMOB_SECRET_KEY=your_secret_key
PAYMOB_PUBLIC_KEY=your_public_key
PAYMOB_CARD_INTEGRATION_ID=<card integration id>
PAYMOB_WALLET_INTEGRATION_ID=<wallet integration id>
PAYMOB_HMAC_SECRET=<the HMAC secret configured on your callback URL>
PAYMOB_SANDBOX_MODE=false
```

Notify Paymob that your callback is `POST {APP_URL}/api/paymob/webhook`
(CSRF-exempt, authenticated purely by HMAC) — see
[`docs/payment-flow.md`](./docs/payment-flow.md) for the full callback and HMAC
details.

## Production deployment

- **Environment safety:** set `APP_ENV=production` and `APP_DEBUG=false` in
  production. `APP_DEBUG=true` exposes exception details and environment
  values to visitors — never ship with it enabled. Keep real Paymob
  credentials, `APP_KEY` and `ADMIN_*` values only in the (git-ignored)
  `.env`.
- **Build assets:** `npm run build` produces the hashed bundle in
  `public/build` that the Blade views reference.
- **Cache the config/routes/views** on every deploy:

  ```bash
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  php artisan event:cache
  ```

  Clear them locally during development (`php artisan optimize:clear`) so
  changes are picked up immediately.
- **Public storage:** run `php artisan storage:link` so product images in
  `storage/app/public` are reachable at `/storage`.
- **Queue:** nothing in the app dispatches jobs yet, but `QUEUE_CONNECTION`
  defaults to `database`. If jobs are added, run a `php artisan queue:work`
  worker and configure a supervisor in production.
- **Security notes:** the Paymob webhook endpoint is the only route exempted
  from CSRF and is authenticated by HMAC-SHA512. Login and registration are
  rate-limited (`throttle:5,1`). Product deletion is blocked while a product is
  referenced by a package or order history — deactivate instead.

## Contributing / code style

- PHP is formatted with [Laravel Pint](https://laravel.com/docs/pint):
  `vendor/bin/pint`.
- Follow the conventions described in [`docs/architecture.md`](./docs/architecture.md)
  (route keys, translatable attributes, the `money()`/`setting()` helpers, the
  database-cache "scalars only" rule).

## License

This application is open-sourced software licensed under the
[MIT license](https://opensource.org/licenses/MIT).# Masr-Security
