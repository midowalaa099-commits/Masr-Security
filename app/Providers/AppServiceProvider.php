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
use App\Services\CatalogCategories;
use App\Services\Payments\PaymobGateway;
use App\Services\SettingsService;
use App\Support\EscapedSQLiteGrammar;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

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
        Event::listen(ConnectionEstablished::class, function (ConnectionEstablished $event): void {
            if ($event->connection instanceof SQLiteConnection) {
                $event->connection->setQueryGrammar(new EscapedSQLiteGrammar($event->connection));
            }
        });

        RateLimiter::for('quote-submissions', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));

        foreach (['saved', 'deleted'] as $event) {
            Category::$event(function (): void {
                CatalogCategories::forget();
                DB::afterCommit(fn () => CatalogCategories::forget());
            });
        }

        Product::updating(function (Product $product): void {
            if ($product->isDirty(['price', 'sale_price'])) {
                $product->pricing_revision = (string) Str::uuid();
            }
        });

        Gate::before(fn (User $user) => $user->isAdmin() ? true : null);
        Password::defaults(function (): Password {
            $rule = Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols();

            return config('app.env') === 'production'
                ? $rule->uncompromised()
                : $rule;
        });

        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Package::class, PackagePolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(QuoteRequest::class, QuoteRequestPolicy::class);
    }
}
