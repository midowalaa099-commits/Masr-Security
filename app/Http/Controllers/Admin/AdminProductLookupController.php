<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProductOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminProductLookupController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, ProductOptions $options): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'page' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'active' => ['nullable', 'boolean'],
        ]);
        $products = $options->search(trim($validated['q'] ?? ''), $request->boolean('active', true), $request->integer('page', 1));

        return response()->json([
            'products' => collect($products->items())->map($options->option(...))->values(),
            'has_more' => $products->hasMorePages(),
        ])->header('Cache-Control', 'private, no-store');
    }
}
