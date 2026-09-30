<?php

namespace App\Http\Requests\Admin;

use App\Models\ProductBrand;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'bail',
                'required',
                'string',
                'max:100',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $normalizedName = mb_strtolower((string) $value);

                    if (ProductBrand::query()->pluck('name')->contains(
                        fn (string $name): bool => mb_strtolower($name) === $normalizedName,
                    )) {
                        $fail(__('validation.unique', ['attribute' => $attribute]));
                    }
                },
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => trim((string) $this->input('name'))]);
    }
}
