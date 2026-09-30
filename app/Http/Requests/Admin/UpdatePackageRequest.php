<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProductStatus;
use App\Services\PackageItemsValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('packages', 'slug')->ignore($this->package)],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:3072'],
            'remove_cover' => ['sometimes', 'boolean'],
            'use_component_pricing' => ['boolean'],
            'base_price' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'discount_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'featured' => ['boolean'],
            'items' => ['nullable', 'array', 'max:'.PackageItemsValidator::MAX_ITEMS],
            'items.*' => ['required', 'array:product_id,quantity'],
            'items.*.product_id' => ['required', 'integer', 'min:1', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
        ];
    }

    public function after(): array
    {
        return [new PackageItemsValidator];
    }
}
