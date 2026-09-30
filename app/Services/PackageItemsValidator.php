<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Validation\Validator;

class PackageItemsValidator
{
    public const MAX_ITEMS = 100;

    public function __invoke(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }
        $items = $validator->getData()['items'] ?? [];
        if ($items === []) {
            return;
        }
        $ids = Product::query()->whereKey(array_column($items, 'product_id'))->pluck('id')->all();
        foreach ($items as $index => $item) {
            if (! in_array((int) $item['product_id'], $ids, true)) {
                $validator->errors()->add("items.{$index}.product_id", __('validation.exists', ['attribute' => "items.{$index}.product id"]));
            }
        }
    }
}
