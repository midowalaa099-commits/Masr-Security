<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ProductImageStorage
{
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
        $contents = $file->get();

        if ($contents === false || $contents === '') {
            throw new RuntimeException('The product image could not be read.');
        }

        return DB::transaction(function () use ($product, $file, $contents, $sortOrder): ProductImage {
            $image = $product->images()->create([
                'path' => 'database',
                'sort_order' => $sortOrder ?? $this->nextSortOrder($product),
            ]);

            $image->content()->create([
                'mime_type' => (string) $file->getMimeType(),
                'contents' => base64_encode($contents),
            ]);

            return $image;
        });
    }

    public function delete(ProductImage $image): void
    {
        if ($image->path !== 'database') {
            Storage::disk('public')->delete($image->path);
        }

        $image->delete();
    }

    private function nextSortOrder(Product $product): int
    {
        return ((int) ($product->images()->max('sort_order') ?? -1)) + 1;
    }
}
