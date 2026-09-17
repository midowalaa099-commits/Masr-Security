<?php

namespace App\Http\Requests;

use App\Support\PhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $phone = PhoneNumber::normalize((string) $this->input('phone', ''));

        $this->merge([
            'email' => strtolower(trim((string) $this->input('email', ''))),
            'phone' => $phone ?? $this->input('phone'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user())],
            'phone' => [
                $this->user()->isAdmin() ? 'nullable' : 'required',
                'regex:/^01[0125][0-9]{8}$/',
                Rule::unique('users', 'phone')->ignore($this->user()),
            ],
            'current_password' => [
                Rule::requiredIf(fn () => $this->input('email') !== $this->user()->email || $this->input('phone') !== $this->user()->phone),
                'nullable',
                'current_password',
            ],
        ];
    }
}
