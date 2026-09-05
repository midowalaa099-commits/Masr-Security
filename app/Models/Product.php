<?php

namespace App\Models;

use App\Contracts\Purchasable;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model implements Purchasable
{
    /** @use HasFactory<ProductFactory> */
    use Concerns\HasTranslatableAttributes, HasFactory;

    protected $fillable = [
        'category_id',
        'sku',
        'name_ar',
        'name_en',
        'slug',
        'description_ar',
        'description_en',
        'brand',
        'model_number',
        'price',
        'sale_price',
        'stock_quantity',
        'low_stock_threshold',
        'status',
        'type',
        'featured',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'featured' => 'boolean',
            'status' => ProductStatus::class,
            'type' => ProductType::class,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function specs(): HasMany
    {
        return $this->hasMany(ProductSpec::class)->orderBy('sort_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Active->value);
    }

    public function isActive(): bool
    {
        return $this->status === ProductStatus::Active;
    }

    public function isOnSale(): bool
    {
        return $this->sale_price !== null
            && $this->sale_price > 0
            && $this->sale_price < $this->price;
    }

    /**
     * The price the customer actually pays, preferring sale price.
     */
    public function effectivePrice(): string
    {
        return $this->isOnSale() ? $this->sale_price : $this->price;
    }

    public function displayPrice(): string
    {
        return $this->effectivePrice();
    }

    public function originalPrice(): string
    {
        return $this->price;
    }

    public function isAvailable(): bool
    {
        return $this->isActive() && $this->stock_quantity > 0;
    }

    public function isOutOfStock(): bool
    {
        return $this->stock_quantity <= 0;
    }

    public function isLowStock(): bool
    {
        return $this->isActive() && ! $this->isOutOfStock() && $this->stock_quantity <= $this->low_stock_threshold;
    }

    // -------- Purchasable contract --------

    public function cartTypeKey(): string
    {
        return 'product';
    }

    public function purchasableName(): string
    {
        return $this->trans('name');
    }

    /**
     * Maximum number of this product that can be fulfilled from current stock.
     */
    public function availableQuantity(): int
    {
        return max(0, (int) $this->stock_quantity);
    }

    public function stockLabel(): string
    {
        if ($this->isOutOfStock()) {
            return __('store.out_of_stock');
        }

        if ($this->isLowStock()) {
            return __('store.low_stock');
        }

        return __('store.in_stock');
    }

    public function firstImageUrl(): ?string
    {
        $image = $this->images()->first();

        return $image?->url;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
