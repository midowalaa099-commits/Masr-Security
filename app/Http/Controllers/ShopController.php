<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()
            ->with(['images', 'category'])
            ->active();

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'like', "%{$search}%")
                    ->orWhere('name_ar', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('model_number', 'like', "%{$search}%");
            });
        }

        if ($categoryId = (int) $request->query('category')) {
            $ids = $this->collectCategoryIds($categoryId);
            $query->whereIn('category_id', $ids);
        }

        $minPrice = $request->filled('min_price') ? (float) $request->query('min_price') : null;
        $maxPrice = $request->filled('max_price') ? (float) $request->query('max_price') : null;

        if ($minPrice !== null || $maxPrice !== null) {
            $query->where(function ($q) use ($minPrice, $maxPrice) {
                // Use effective (sale) price when available.
                $q->where(function ($q2) use ($minPrice, $maxPrice) {
                    $q2->whereNotNull('sale_price');
                    if ($minPrice !== null) {
                        $q2->where('sale_price', '>=', $minPrice);
                    }
                    if ($maxPrice !== null) {
                        $q2->where('sale_price', '<=', $maxPrice);
                    }
                })->orWhere(function ($q2) use ($minPrice, $maxPrice) {
                    $q2->whereNull('sale_price');
                    if ($minPrice !== null) {
                        $q2->where('price', '>=', $minPrice);
                    }
                    if ($maxPrice !== null) {
                        $q2->where('price', '<=', $maxPrice);
                    }
                });
            });
        }

        $sort = $request->query('sort', 'newest');

        switch ($sort) {
            case 'price_asc':
                $query->orderByRaw('COALESCE(sale_price, price) ASC');
                break;
            case 'price_desc':
                $query->orderByRaw('COALESCE(sale_price, price) DESC');
                break;
            case 'name':
                $column = app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';
                $query->orderBy($column);
                break;
            default:
                $query->latest();
        }

        $products = $query->paginate(12)->withQueryString();

        $categories = Category::query()
            ->where('is_active', true)
            ->with(['children' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->get();

        return view('store.shop', compact('products', 'categories'));
    }

    private function collectCategoryIds(int $categoryId): array
    {
        $ids = [$categoryId];

        Category::query()
            ->where('parent_id', $categoryId)
            ->pluck('id')
            ->each(fn ($id) => $ids[] = $id);

        return array_unique($ids);
    }
}
