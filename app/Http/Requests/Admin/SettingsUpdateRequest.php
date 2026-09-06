<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class SettingsUpdateRequest extends FormRequest
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
            'company_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'shipping_fee' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'facebook' => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'url', 'max:255'],
            'google_maps' => ['nullable', 'string', 'max:2000'],
            'hero_title_en' => ['nullable', 'string', 'max:255'],
            'hero_title_ar' => ['nullable', 'string', 'max:255'],
            'hero_subtitle_en' => ['nullable', 'string', 'max:500'],
            'hero_subtitle_ar' => ['nullable', 'string', 'max:500'],
            'site_logo' => ['nullable', File::image(allowSvg: true)->max('2mb')],
            'remove_site_logo' => ['sometimes', 'boolean'],
            'hero_image' => ['nullable', File::image()->max('4mb')],
            'remove_hero_image' => ['sometimes', 'boolean'],
            'gallery_images' => ['nullable', 'array', 'max:8'],
            'gallery_images.*' => [File::image()->max('4mb')],
            'remove_gallery_images' => ['sometimes', 'boolean'],
            'why_points_en' => ['nullable', 'string', 'max:2000'],
            'why_points_ar' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
