# Architecture

MASR Security is a bilingual (English / Arabic, LTR / RTL) e-commerce application
for an Egyptian Hikvision value-added distributor. It is built with **Laravel 13**
(PHP 8.3+), Blade + Alpine.js, Tailwind CSS v4, MySQL, and Paymob as the payment
gateway.

## Stack

| Layer | Technology |
| --- | --- |
| Framework | Laravel 13 (`^13.17`), PHP `^8.3` |
| Frontend | Blade, Alpine.js (`^3.4`), Tailwind CSS v4 (Vite) |
| Database | MySQL (dev `masr_security`, tests use in-memory SQLite) |
| Session / Cache / Queue | Database driver |
| Auth | Laravel Breeze (Blade) with server-side pools for Cart |
| Payments | Paymob Intention API + HMAC-SHA512 callbacks, local sandbox |

## Folder map

```
app/
  Contracts/          PaymentGatewayInterface, Purchasable
  Enums/              OrderStatus, PaymentStatus, PaymentMethod, ProductStatus, ProductType, QuoteStatus, UserRole
  Http/
    Controllers/      Storefront, Account, Admin/*, Payments/* (webhook + sandbox), Auth/* (Breeze)
    Middleware/       EnsureUserIsAdmin, ShareStorefrontData, SetLocale
    Requests/         FormRequest classes for checkout, quote, admin CRUD
  Models/
    Concerns/         HasTranslatableAttributes
    Category, Product, ProductSpec, ProductImage,
    Package, PackageItem,
    Cart, CartItem,
    Order, OrderItem, Payment, PaymentCallbackResult,
    QuoteRequest, AuditLog, Setting, User
  Policies/           Order, Category, Product, Package, QuoteRequest, AdminCrud
  Services/
    CartService, CheckoutService, InventoryService,
    PaymentService, SettingsService, AuditLogger,
    Payments/PaymobGateway
    (value object: CartItemValue)
config/
  paymob.php          Gateway credentials (env-driven)
database/
  factories/          Factories for every model
  seeders/            Settings, Admin, Category, Product, Package, DatabaseSeeder
routes/
  web.php             Storefront, Account, Payments, Admin
lang/{en,ar}/         store.php, admin.php, payments.php
resources/views/
  store/ + components/store/   Storefront UI
  admin/  + components/admin/  Admin UI
  account/                      Customer account pages
  auth/                         Breeze auth pages
```

## Request lifecycle

1. `SetLocale` sets the app locale from the `locale` cookie (or `en`).
2. `ShareStorefrontData` shares, for storefront routes:
   - navigation categories (top-level, with active children),
   - all site settings (`SettingsService`),
   - the cart item badge count.
3. `EnsureUserIsAdmin` gates the whole `/admin` prefix — **server-side only**,
   never relying on hidden UI links.

## Key domains

### Catalog

- **Category** — top-level categories with an ordered child list (used by nav).
- **Product** — `type` is `simple` (standalone sellable) or `component`
  (only sold as part of packages). Each product has spec rows
  (`product_specs`), images (`product_images`), price / optional sale price,
  stock quantity, and a low-stock threshold.
- **Package** — a sellable kit of components. Availability and price are
  *derived* from its `package_items` (product + quantity). Pricing mode is
  `use_component_pricing` (component total minus `discount_amount`) or a fixed
  `base_price`. Price is `displayPrice()`, pre-discount original is
  `originalPrice()`; the cart shows the savings.
- Products and packages implement the `Purchasable` contract and bind routes by
  `slug`.

### Cart

`CartService` supports two modes:

- **Guest**: line items live in the session under `cart.items`.
- **Authenticated**: line items persist in `carts` / `cart_items` and survive
  login/logout.

Totals and stock are always recomputed from the database (`CartItemValue`
immutables), never trusted from the browser. Cart lines clamp to available
stock.

### Checkout & inventory

`CheckoutService::placeOrder()` runs inside **one DB transaction** with row
locks (`lockForUpdate`):

1. Re-validate the cart is non-empty.
2. Lock every component product row and re-check availability against the
   locked values.
3. Recompute subtotal, shipping (from `settings.shipping_fee`), and total.
4. Persist the order + `order_items` with an `inventory_snapshot`
   (`product_id => quantity`) so later stock restore is exact.
5. Decrement stock (`InventoryService::decrementForOrder`) — never negative.
6. Clear the cart.

`InventoryService::restoreForOrder()` returns stock when an admin cancels an
order that is still before fulfillment (`pending`, `awaiting_payment`, `paid`
or `processing`). Once shipped/delivered, stock is not restored.

### Orders & payments (see `docs/payment-flow.md` for the full flow)

- `Order` states: `pending → awaiting_payment → paid → processing →
  shipped → delivered`, or `cancelled`.
- `Payment` rows are always created pending; the gateway webhook/redirect moves
  them to `success`, `failed`, or `cancelled`. Transitions are applied through
  `PaymentService::applyResult()` which is **idempotent** (a final payment is
  never double-processed) and run in a transaction with a locked payment row.
- On success the order moves `pending/awaiting_payment → paid`. On failure,
  an unpaid order stays `awaiting_payment`.

### CMS / shared settings

`SettingsService` reads the `settings` table into a forever-cached `key => value`
array (safe for the database cache store because it only caches scalars). The
admin Settings screen edits exactly the keys in
`AdminSettingsController::EDITABLE_KEYS` (company info, shipping fee, hero copy
in en/ar, social links, map embed).

> **Cache store warning:** the app uses `CACHE_STORE=database`. Laravel 13's
> database cache unserializes without allowing arbitrary classes. Never cache
> Eloquent models — cache scalar values (e.g. category *ids* in
> `ShareStorefrontData`) and re-query. See `docs/payment-flow.md` for the same
> constraint applied to payments.

### Audit logging

`AuditLogger` writes rows to `audit_logs` for admin actions (product/category/
package CRUD, order status changes, payment status changes). Dashboard and admin
list pages never rely on a memory-only store.

## Admin

All routes live under the `admin.` name prefix and the `/admin` URL, protected
by `auth` + the `admin` alias (`EnsureUserIsAdmin`). Policies further guard
model access; the `AdminCrudPolicy` authorizes `create/store/update/destroy` for
admins. Admin pages: dashboard, categories, products (with images + specs),
packages (with an AJAX line-total calculator), orders (status + tracking),
payments, quote requests, settings, audit logs.

After login the user is redirected to `/dashboard`, which roles the request:
admins go to `admin.dashboard`, customers to `account.dashboard`.

## Internationalisation

- Locale defaults to `en`; `ar` switches the whole UI and adds `dir="rtl"` via
  the layout.
- Translatable model attributes (`name`, `description`, hero copy) use the
  `HasTranslatableAttributes` concern: `$model->trans('name')` returns the
  attribute for the active locale, falling back to English.
- Translations live in `lang/{en,ar}/{store,admin,payments}.php`.

## Localisation & conventions

- Money is always formatted through the `money()` helper
  (`number_format(..., 2) . ' EGP'`).
- `setting('key')` reads a cached site setting.
- Model route bindings for catalog entities use `slug`;
  order/payment/quote bindings use integer ids.

## Concurrency rules

- Stock: `decrementForOrder` and the checkout availability check both lock the
  physical product rows. Never insert ad-hoc stock mutations outside a
  transaction with locks.
- Payments: `applyResult` re-locks the payment row, and webhook duplicates with
  an identical final status are ignored.

## Tests

Feature tests live in `tests/Feature` and use `RefreshDatabase` with in-memory
SQLite (see `phpunit.xml`). Current suite covers the storefront, cart, checkout,
admin, and Paymob HMAC handling. Run with `php artisan test`.