<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UniqueSlugGenerator
{
    public function generate(string $value, string $fallback, Model $model, ?Model $ignore = null): string
    {
        $base = Str::slug($value) ?: $fallback;
        $slug = $base;
        $suffix = 2;

        while (true) {
            $query = $model->newQuery()->where('slug', $slug);

            if ($ignore !== null) {
                $query->where($model->getKeyName(), '!=', $ignore->getKey());
            }

            if (! $query->exists()) {
                return $slug;
            }

            $slug = $base.'-'.$suffix++;
        }
    }
}
