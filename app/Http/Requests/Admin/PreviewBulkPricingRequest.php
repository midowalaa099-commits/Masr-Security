<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreviewBulkPricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'scope' => ['required', Rule::in(['all', 'brand', 'category', 'selected'])],
            'brand' => ['exclude_unless:scope,brand', 'bail', 'required', 'string', 'max:100', function (string $attribute, mixed $value, Closure $fail): void {
                if (! Product::query()->whereRaw('LOWER(TRIM(brand)) = ?', [mb_strtolower(trim($value))])->exists()) {
                    $fail(__('validation.exists', ['attribute' => __('pricing.brand')]));
                }
            }],
            'category_id' => ['exclude_unless:scope,category', 'required', 'integer', 'exists:categories,id'],
            'product_ids' => ['exclude_unless:scope,selected', 'required', 'array', 'min:1', 'max:1000'],
            'product_ids.*' => ['integer', 'distinct', 'exists:products,id'],
            'operation' => ['required', Rule::in(['percentage', 'fixed'])],
            'direction' => ['required', Rule::in(['increase', 'decrease'])],
            'amount' => ['required', 'string', 'regex:/^\d{1,9}(\.\d{1,4})?$/', 'numeric', 'gt:0', 'max:999999999'],
            'target' => ['required', Rule::in(['regular', 'sale', 'both'])],
            'rounding' => ['required', Rule::in(['precision', '5', '10'])],
        ];
    }
}
