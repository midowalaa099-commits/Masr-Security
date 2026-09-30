<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class CatalogSection
{
    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            'all' => app()->getLocale() === 'ar' ? 'جميع المنتجات' : 'All Products',
            'hikvision' => 'Hikvision',
            'hilook' => 'HiLook',
            'ezviz' => 'EZVIZ',
            'audio-systems' => app()->getLocale() === 'ar' ? 'الأنظمة الصوتية' : 'Audio Systems',
        ];
    }

    public static function normalize(mixed $section): string
    {
        $section = is_string($section) ? mb_strtolower(trim($section)) : '';

        return array_key_exists($section, self::options()) ? $section : 'all';
    }

    /** @param list<int> $audioCategoryIds */
    public static function apply(Builder $query, string $section, array $audioCategoryIds): void
    {
        if ($section === 'audio-systems') {
            $query->whereIn('category_id', $audioCategoryIds);
        } elseif (in_array($section, ['hikvision', 'hilook', 'ezviz'], true)) {
            $query->whereRaw('LOWER(TRIM(brand)) = ?', [$section]);
        }
    }
}
