<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\AdminAuditLogController;
use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminPackageController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminQuoteController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\Payments\PaymobWebhookController;
use App\Http\Controllers\Payments\SandboxPaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\QuoteRequestController;
use App\Http\Controllers\ShopController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront
|--------------------------------------------------------------------------
*/

Route::middleware('storefront')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    Route::get('/shop', [ShopController::class, 'index'])->name('shop');
    Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');

    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

    Route::get('/packages', [PackageController::class, 'index'])->name('packages.index');
    Route::get('/packages/{package}', [PackageController::class, 'show'])->name('packages.show');

    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/{type}/{cartable}', [CartController::class, 'update'])
        ->whereIn('type', ['product', 'package'])
        ->name('cart.update');
    Route::delete('/cart/{type}/{cartable}', [CartController::class, 'remove'])
        ->whereIn('type', ['product', 'package'])
        ->name('cart.remove');
    Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::get('/checkout/return/{payment}', [CheckoutController::class, 'return'])->name('checkout.return');
    Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');

    Route::get('/about', [AboutController::class, 'index'])->name('about');
    Route::get('/contact', [ContactController::class, 'index'])->name('contact');

    Route::get('/quote', [QuoteRequestController::class, 'create'])->name('quote.create');
    Route::post('/quote', [QuoteRequestController::class, 'store'])->name('quote.store');

    Route::match(['get', 'post'], '/locale/{locale}', [LocaleController::class, 'switch'])
        ->whereIn('locale', SetLocale::SUPPORTED)
        ->name('locale.switch');
});

/*
|--------------------------------------------------------------------------
| Customer account
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'storefront'])->prefix('account')->name('account.')->group(function () {
    Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
    Route::get('/password', [AccountController::class, 'password'])->name('password');
    Route::get('/orders', [AccountController::class, 'orders'])->name('orders');
    Route::get('/orders/{order}', [AccountController::class, 'show'])->name('orders.show');
});

Route::get('/dashboard', function () {
    return auth()->user()?->isAdmin()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('account.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| Payments
|--------------------------------------------------------------------------
|
| The Paymob webhook is a server-to-server POST and must not require a
| session or CSRF token. Signature (HMAC) validation happens inside the
| handler.
*/

Route::post('/api/paymob/webhook', [PaymobWebhookController::class, 'handle'])->name('payments.webhook');

Route::middleware('storefront')->group(function () {
    Route::get('/payments/sandbox/{payment}', [SandboxPaymentController::class, 'show'])
        ->name('payments.sandbox');
    Route::post('/payments/sandbox/{payment}/complete', [SandboxPaymentController::class, 'complete'])
        ->name('payments.sandbox.complete');
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

Route::prefix('admin')
    ->middleware(['auth', 'admin'])
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::resource('categories', AdminCategoryController::class)->except(['show']);
        Route::patch('categories/{category}/toggle', [AdminCategoryController::class, 'toggle'])->name('categories.toggle');

        Route::resource('products', AdminProductController::class);
        Route::post('products/{product}/images', [AdminProductController::class, 'storeImage'])->name('products.images.store');
        Route::delete('products/images/{image}', [AdminProductController::class, 'destroyImage'])->name('products.images.destroy');
        Route::post('products/images/reorder', [AdminProductController::class, 'reorderImages'])->name('products.images.reorder');

        Route::resource('packages', AdminPackageController::class);
        Route::post('packages/calculate', [AdminPackageController::class, 'calculate'])->name('packages.calculate');

        Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');

        Route::get('payments', [AdminPaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/{payment}', [AdminPaymentController::class, 'show'])->name('payments.show');

        Route::get('settings', [AdminSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [AdminSettingsController::class, 'update'])->name('settings.update');

        Route::get('quotes', [AdminQuoteController::class, 'index'])->name('quotes.index');
        Route::get('quotes/{quote}', [AdminQuoteController::class, 'show'])->name('quotes.show');
        Route::post('quotes/{quote}/status', [AdminQuoteController::class, 'updateStatus'])->name('quotes.status');

        Route::get('audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs.index');
    });

require __DIR__.'/auth.php';
