<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
            'category_id' => ['nullable', 'exists:categories,id'],
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($this->product)],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($this->product)],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model_number' => ['nullable', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:999999999'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'max:999999999', 'lt:price'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'low_stock_threshold' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'type' => ['required', Rule::enum(ProductType::class)],
            'featured' => ['boolean'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['image', 'mimes:jpeg,png,webp', 'max:3072'],
            'specs' => ['nullable', 'array'],
            'specs.*' => ['nullable', 'array'],
            'specs.*.key' => ['nullable', 'string', 'max:100'],
            'specs.*.value_ar' => ['nullable', 'string', 'max:255'],
            'specs.*.value_en' => ['nullable', 'string', 'max:255'],
        ];
    }
}
