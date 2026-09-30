<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductImageStorage
{
    public function __construct(private readonly MediaStorage $storage) {}

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function storeMany(Product $product, array $files): void
    {
        $sortOrder = $this->nextSortOrder($product);

        foreach ($files as $file) {
            $this->store($product, $file, $sortOrder++);
        }
    }

    public function store(Product $product, UploadedFile $file, ?int $sortOrder = null): ProductImage
    {
        $storageKey = $this->storage->store($file, 'products');

        try {
            return $product->images()->create([
                'path' => 'supabase',
                'storage_key' => $storageKey,
                'sort_order' => $sortOrder ?? $this->nextSortOrder($product),
            ]);
        } catch (Throwable $persistenceException) {
            try {
                $this->storage->delete($storageKey);
            } catch (Throwable $cleanupException) {
                Log::error('product_image.upload_cleanup_failed', [
                    'storage_key' => $storageKey,
                    'exception' => $cleanupException,
                ]);
            }

            throw $persistenceException;
        }
    }

    public function delete(ProductImage $image): void
    {
        if ($image->storage_key !== null) {
            $this->storage->delete($image->storage_key);
        } elseif ($image->path !== 'database') {
            $this->storage->delete($image->path);
        }

        $image->delete();
    }

    private function nextSortOrder(Product $product): int
    {
        return ((int) ($product->images()->max('sort_order') ?? -1)) + 1;
    }
}
