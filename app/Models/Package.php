<?php

namespace App\Models;

use App\Contracts\Purchasable;
use App\Enums\ProductStatus;
use Database\Factories\PackageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

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

    public function items(): HasMany
    {
        return $this->hasMany(PackageItem::class)->with('product');
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
        $total = 0.0;

        foreach ($this->items as $item) {
            $total += (float) $item->product->displayPrice() * $item->quantity;
        }

        return number_format(round($total, 2), 2, '.', '');
    }

    public function useComponentPricing(): bool
    {
        return (bool) $this->use_component_pricing;
    }

    public function effectivePricing(): string
    {
        if (! $this->useComponentPricing() && $this->base_price !== null) {
            return number_format((float) $this->base_price, 2, '.', '');
        }

        return number_format(max(0, (float) $this->componentsTotal() - (float) $this->discount_amount), 2, '.', '');
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

    /**
     * Maximum number of this package that can currently be fulfilled
     * based on its components stock, or 0 when a component is missing/inactive.
     */
    public function availableQuantity(): int
    {
        if ($this->items->isEmpty()) {
            return 0;
        }

        $min = null;

        foreach ($this->items as $item) {
            $product = $item->product;

            if ($product === null || ! $product->isActive()) {
                return 0;
            }

            $possible = intdiv((int) $product->stock_quantity, max(1, (int) $item->quantity));

            $min = $min === null ? $possible : min($min, $possible);
        }

        return $min ?? 0;
    }

    public function isAvailable(): bool
    {
        return $this->effectivePricing() > 0 && $this->availableQuantity() > 0 && $this->status === ProductStatus::Active;
    }

    public function isOutOfStock(): bool
    {
        return $this->items->isEmpty() || $this->availableQuantity() <= 0;
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
            return Storage::disk('public')->url($this->cover_image);
        }

        $item = $this->items->first();

        return $item?->product?->firstImageUrl();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
