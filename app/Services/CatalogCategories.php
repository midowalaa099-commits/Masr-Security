<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class CatalogCategories
{
    private const KEY = 'storefront.categories.v1';

    /** @return Collection<int, Category> */
    public function active(): Collection
    {
        $rows = Cache::remember(self::KEY, 300, fn (): array => Category::query()
            ->active()->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn (Category $category): array => $category->getAttributes())
            ->all());

        return Category::hydrate($rows);
    }

    public static function forget(): void
    {
        Cache::forget(self::KEY);
    }
}
