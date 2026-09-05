<?php

namespace App\Http\Middleware;

use App\Models\Category;
use App\Services\CartService;
use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shares storefront-wide data (settings, navigation categories, cart badge)
 * with every storefront view without touching every controller.
 */
class ShareStorefrontData
{
    public function handle(Request $request, Closure $next): Response
    {
        $categories = $this->navigationCategories();

        $settings = app(SettingsService::class);
        $cartCount = app(CartService::class)->count();

        View::share([
            'storefrontNavigationCategories' => $categories,
            'storeSettings' => $settings,
            'storefrontCartCount' => $cartCount,
        ]);

        return $next($request);
    }

    private function navigationCategories()
    {
        $ids = Cache::remember('storefront.nav.category_ids', 3600, function () {
            return Category::query()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->pluck('id')
                ->all();
        });

        return Category::query()
            ->whereKey($ids)
            ->where('is_active', true)
            ->with(['children' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->get();
    }
}
