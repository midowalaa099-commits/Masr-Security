<?php

namespace App\Services;

use App\Models\Product;
use App\Support\SearchPattern;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ProductOptions
{
    public const PAGE_SIZE = 50;

    /** @param list<int> $selectedIds
     * @return Collection<int, Product>
     */
    public function initial(array $selectedIds = [], bool $activeOnly = true): Collection
    {
        $products = $this->query($activeOnly)->limit(self::PAGE_SIZE)->get();
        if ($selectedIds !== []) {
            $products = $products->merge($this->query(false)->whereKey($selectedIds)->get());
        }

        return $products;
    }

    /** @return Paginator<int, Product> */
    public function search(string $search, bool $activeOnly, int $page): Paginator
    {
        return $this->query($activeOnly)->when($search !== '', function (Builder $query) use ($search): void {
            $pattern = SearchPattern::contains($search);
            $query->where(fn (Builder $query) => $query->whereLike('name_en', $pattern, caseSensitive: false)
                ->orWhereLike('name_ar', $pattern, caseSensitive: false)
                ->orWhereLike('sku', $pattern, caseSensitive: false));
        })->simplePaginate(self::PAGE_SIZE, page: $page);
    }

    /** @return array{id: int, name: string, sku: string, price: string} */
    public function option(Product $product): array
    {
        return ['id' => $product->id, 'name' => $product->trans('name'), 'sku' => $product->sku, 'price' => $product->displayPrice()];
    }

    /** @return Builder<Product> */
    private function query(bool $activeOnly): Builder
    {
        return Product::query()->select(['id', 'sku', 'name_en', 'name_ar', 'price', 'sale_price'])
            ->when($activeOnly, fn (Builder $query) => $query->active())
            ->orderBy('name_en')->orderBy('id');
    }
}
