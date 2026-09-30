<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CatalogCategories;
use App\Support\CatalogSection;
use App\Support\SearchPattern;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(Request $request, CatalogCategories $catalogCategories): View
    {
        $allCategories = $catalogCategories->active();
        $categoryGroups = $allCategories->groupBy('parent_id');
        $categories = $categoryGroups->get('', collect());
        $section = CatalogSection::normalize($request->query('section'));
        $sections = CatalogSection::options();
        $audioCategoryIds = [];
        foreach ($allCategories->where('slug', 'audio-systems') as $audioCategory) {
            $audioCategoryIds = array_merge($audioCategoryIds, $this->collectCategoryIds($audioCategory->id, $categoryGroups));
        }

        $query = Product::query()
            ->with(['images', 'category'])
            ->active();

        CatalogSection::apply($query, $section, $audioCategoryIds);

        if ($section !== 'all') {
            $relevantIds = (clone $query)->reorder()->distinct()->pluck('category_id')->filter()->all();
            foreach ($relevantIds as $id) {
                $ancestor = $allCategories->firstWhere('id', $id);
                $visited = [];
                while ($ancestor && ! in_array($ancestor->id, $visited, true)) {
                    $visited[] = $ancestor->id;
                    $relevantIds[] = $ancestor->id;
                    $ancestor = $allCategories->firstWhere('id', $ancestor->parent_id);
                }
            }
            $visibleCategories = $allCategories->whereIn('id', $relevantIds);
            $categoryGroups = $visibleCategories->groupBy('parent_id');
            $categories = $categoryGroups->get('', collect());
        }

        if ($search = trim((string) $request->query('q'))) {
            $search = SearchPattern::contains($search);
            $query->where(function ($q) use ($search) {
                $q->whereLike('name_en', $search, caseSensitive: false)
                    ->orWhereLike('name_ar', $search, caseSensitive: false)
                    ->orWhereLike('sku', $search, caseSensitive: false)
                    ->orWhereLike('model_number', $search, caseSensitive: false);
            });
        }

        if ($categoryId = (int) $request->query('category')) {
            $ids = $allCategories->contains('id', $categoryId)
                ? $this->collectCategoryIds($categoryId, $categoryGroups)
                : [];
            $query->whereIn('category_id', $ids);
        }

        $minPrice = $request->filled('min_price') ? (float) $request->query('min_price') : null;
        $maxPrice = $request->filled('max_price') ? (float) $request->query('max_price') : null;

        $effectivePrice = 'CASE WHEN sale_price > 0 AND sale_price < price THEN sale_price ELSE price END';
        if ($minPrice !== null) {
            $query->whereRaw("($effectivePrice) >= CAST(? AS DECIMAL(12, 2))", [$minPrice]);
        }
        if ($maxPrice !== null) {
            $query->whereRaw("($effectivePrice) <= CAST(? AS DECIMAL(12, 2))", [$maxPrice]);
        }

        $sort = $request->query('sort', 'newest');

        switch ($sort) {
            case 'price_asc':
                $query->orderByRaw("($effectivePrice) ASC");
                break;
            case 'price_desc':
                $query->orderByRaw("($effectivePrice) DESC");
                break;
            case 'name':
                $column = app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';
                $query->orderBy($column);
                break;
            default:
                $query->latest();
        }

        $products = $query->orderBy('id')->paginate(12)->withQueryString();

        $expandedCategoryIds = [];
        $selectedCategory = $allCategories->firstWhere('id', (int) $request->query('category'));
        while ($selectedCategory && ! in_array($selectedCategory->id, $expandedCategoryIds, true)) {
            $expandedCategoryIds[] = $selectedCategory->id;
            $selectedCategory = $allCategories->firstWhere('id', $selectedCategory->parent_id);
        }

        return view('store.shop', compact('products', 'categories', 'categoryGroups', 'expandedCategoryIds', 'section', 'sections'));
    }

    /** @return list<int> */
    private function collectCategoryIds(int $categoryId, Collection $categoryGroups): array
    {
        $ids = [];
        $pending = [$categoryId];
        while ($pending !== []) {
            $id = array_pop($pending);
            if (in_array($id, $ids, true)) {
                continue;
            }
            $ids[] = $id;
            foreach ($categoryGroups->get($id, collect()) as $child) {
                $pending[] = $child->id;
            }
        }

        return $ids;
    }
}
