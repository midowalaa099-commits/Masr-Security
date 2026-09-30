<?php

namespace App\Http\Middleware;

use App\Services\CartService;
use App\Services\CatalogCategories;
use App\Services\SettingsService;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
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
        if (! $request->isMethod('GET') || $request->routeIs('locale.switch')) {
            return $next($request);
        }

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

    private function navigationCategories(): Collection
    {
        $categories = app(CatalogCategories::class)->active();
        $groups = $categories->groupBy('parent_id');
        foreach ($categories as $category) {
            $category->setRelation('children', $groups->get($category->id, new Collection));
        }

        return $categories->whereNull('parent_id');
    }
}
