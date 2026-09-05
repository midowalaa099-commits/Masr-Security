<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

class CategoryController extends Controller
{
    public function show(Category $category)
    {
        abort_unless($category->is_active, 404);

        $ids = collect($category->children()->where('is_active', true)->pluck('id'))->push($category->id)->all();

        $products = Product::query()
            ->active()
            ->whereIn('category_id', $ids)
            ->with(['images', 'category'])
            ->latest()
            ->paginate(12);

        return view('store.category', compact('category', 'products', 'ids'));
    }
}
