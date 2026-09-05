<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Adds a helper to read bilingual attributes based on the current locale.
 *
 * Models using this trait must have columns like `name_ar` / `name_en`
 * (or `description_ar` / `description_en`) and plain `name`/`description`
 * fallbacks are attempted last.
 */
trait HasTranslatableAttributes
{
    public function trans(string $attribute): string
    {
        $locale = app()->getLocale();

        // Specific order: current locale -> English -> Arabic -> plain key.
        $keys = [$attribute.'_'.$locale, $attribute.'_en', $attribute.'_ar', $attribute];

        foreach ($keys as $key) {
            $value = $this->{$key} ?? null;
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return $this->{$attribute} ?? '';
    }

    public function transAttribute(string $attribute): string
    {
        $locale = app()->getLocale();

        $candidate = $attribute.'_'.$locale;
        $fallback = $locale === 'en' ? $attribute.'_ar' : $attribute.'_en';

        $value = $this->{$candidate} ?? null;

        if (is_string($value) && trim($value) !== '') {
            return $candidate;
        }

        $fallbackValue = $this->{$fallback} ?? null;

        if (is_string($fallbackValue) && trim($fallbackValue) !== '') {
            return $fallback;
        }

        return $candidate;
    }

    public function localizedSlug(): string
    {
        if (! method_exists($this, 'trans')) {
            return (string) $this->slug;
        }

        $name = $this->trans('name');

        return $this->slug ?? Str::slug($name);
    }
}
