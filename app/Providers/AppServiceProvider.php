<?php

namespace App\Providers;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Category;
use App\Models\Order;
use App\Models\Package;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Policies\CategoryPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PackagePolicy;
use App\Policies\ProductPolicy;
use App\Policies\QuoteRequestPolicy;
use App\Services\Payments\PaymobGateway;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SettingsService::class);

        $this->app->bind(PaymentGatewayInterface::class, PaymobGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(fn (User $user) => $user->isAdmin() ? true : null);

        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Package::class, PackagePolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(QuoteRequest::class, QuoteRequestPolicy::class);
    }
}
