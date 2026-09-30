<?php

namespace App\Models;

use Database\Factories\ProductImageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductImage extends Model
{
    /** @use HasFactory<ProductImageFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'path',
        'storage_key',
        'sort_order',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function content(): HasOne
    {
        return $this->hasOne(ProductImageContent::class);
    }

    public function getUrlAttribute(): ?string
    {
        if (! $this->path) {
            return null;
        }

        if ($this->storage_key !== null) {
            return media_url($this->storage_key);
        }

        return $this->path === 'database' ? route('product-images.show', $this) : media_url($this->path);
    }

    public function getPathFilenameAttribute(): string
    {
        return basename((string) ($this->storage_key ?? $this->path));
    }
}
