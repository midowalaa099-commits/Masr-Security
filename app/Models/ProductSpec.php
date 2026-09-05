<?php

namespace App\Models;

use Database\Factories\ProductSpecFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSpec extends Model
{
    /** @use HasFactory<ProductSpecFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'spec_key',
        'spec_value_ar',
        'spec_value_en',
        'sort_order',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Localized spec value based on the current app locale.
     */
    public function value(): string
    {
        $locale = app()->getLocale();

        $primary = $locale === 'en' ? $this->spec_value_en : $this->spec_value_ar;

        if ($primary !== null && $primary !== '') {
            return $primary;
        }

        $fallback = $locale === 'en' ? $this->spec_value_ar : $this->spec_value_en;

        return $fallback ?? '';
    }
}
