<?php

namespace App\Models;

use App\Contracts\Purchasable;
use App\Enums\ProductStatus;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Database\Factories\PackageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string|null $base_price
 */
class Package extends Model implements Purchasable
{
    /** @use HasFactory<PackageFactory> */
    use Concerns\HasTranslatableAttributes, HasFactory;

    protected $fillable = [
        'name_ar',
        'name_en',
        'slug',
        'description_ar',
        'description_en',
        'cover_image',
        'base_price',
        'use_component_pricing',
        'discount_amount',
        'status',
        'featured',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'use_component_pricing' => 'boolean',
            'featured' => 'boolean',
            'status' => ProductStatus::class,
        ];
    }

    /** @return HasMany<PackageItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(PackageItem::class)->with('product');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Active->value);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'package_items')
            ->withPivot('quantity');
    }

    /**
     * Sum of component prices before any package discount.
     */
    public function componentsTotal(): string
    {
        $total = BigDecimal::of('0.00');

        foreach ($this->items as $item) {
            if ($item->product !== null) {
                $total = $total->plus(BigDecimal::of($item->product->displayPrice())->multipliedBy($item->quantity));
            }
        }

        return (string) $total->toScale(2, RoundingMode::HalfUp);
    }

    public function useComponentPricing(): bool
    {
        return (bool) $this->use_component_pricing;
    }

    public function effectivePricing(): string
    {
        if (! $this->useComponentPricing() && $this->base_price !== null) {
            return $this->base_price;
        }

        return (string) BigDecimal::max('0', BigDecimal::of($this->componentsTotal())->minus($this->discount_amount ?? '0'))->toScale(2, RoundingMode::HalfUp);
    }

    // -------- Purchasable contract --------

    public function cartTypeKey(): string
    {
        return 'package';
    }

    public function purchasableName(): string
    {
        return $this->trans('name');
    }

    public function displayPrice(): string
    {
        return $this->effectivePricing();
    }

    public function originalPrice(): string
    {
        return $this->componentsTotal();
    }

    /** Packages can be ordered when all component products are active. */
    public function availableQuantity(): ?int
    {
        if ($this->items->isEmpty()) {
            return 0;
        }

        foreach ($this->items as $item) {
            $product = $item->product;

            if ($product === null || ! $product->isActive()) {
                return 0;
            }

        }

        return null;
    }

    public function isAvailable(): bool
    {
        return $this->effectivePricing() > 0 && $this->availableQuantity() === null && $this->status === ProductStatus::Active;
    }

    public function isOutOfStock(): bool
    {
        return $this->availableQuantity() === 0;
    }

    public function stockLabel(): string
    {
        if ($this->isOutOfStock()) {
            return __('store.out_of_stock');
        }

        return __('store.in_stock');
    }

    public function firstImageUrl(): ?string
    {
        if ($this->cover_image) {
            return media_url($this->cover_image);
        }

        $item = $this->items->first();

        return $item?->product?->firstImageUrl();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
