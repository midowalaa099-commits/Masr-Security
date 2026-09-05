<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Package;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $featuredProducts = Product::query()
            ->active()
            ->where('featured', true)
            ->with(['images', 'category'])
            ->latest()
            ->limit(8)
            ->get();

        $featuredPackages = Package::query()
            ->where('status', 'active')
            ->where('featured', true)
            ->with('items.product.images')
            ->latest()
            ->limit(3)
            ->get();

        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => fn ($q) => $q->where('is_active', true)])
            ->withCount(['products' => fn ($q) => $q->active()])
            ->orderBy('sort_order')
            ->get();

        $displayCategory = Category::query()->where('slug', 'interactive-displays')->first();

        $displays = $displayCategory
            ? Product::query()->active()->where('category_id', $displayCategory->id)->with(['images'])->latest()->limit(4)->get()
            : collect();

        return view('store.home', compact('featuredProducts', 'featuredPackages', 'categories', 'displays'));
    }
}
