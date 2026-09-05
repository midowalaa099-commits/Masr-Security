<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ProductController extends Controller
{
    public function show(Product $product)
    {
        abort_unless($product->isActive(), 404);

        $product->load(['images', 'specs', 'category']);

        $related = Product::query()
            ->active()
            ->where('id', '!=', $product->id)
            ->where('category_id', $product->category_id)
            ->with(['images'])
            ->latest()
            ->limit(4)
            ->get();

        return view('store.product', compact('product', 'related'));
    }
}
