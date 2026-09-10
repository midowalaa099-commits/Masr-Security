<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_storefront_fallback_is_used_for_the_matching_product(): void
    {
        $product = Product::factory()->create(['sku' => 'HIK-DOM-4MP']);

        $this->assertSame(
            'https://upload.wikimedia.org/wikipedia/commons/thumb/7/71/HIK-VISION_security_camera.JPG/960px-HIK-VISION_security_camera.JPG',
            $product->firstImageUrl(),
        );
    }

    public function test_uploaded_product_image_takes_priority_over_the_fallback(): void
    {
        $product = Product::factory()->create(['sku' => 'HIK-DOM-4MP']);
        $product->images()->create(['path' => 'products/exact-camera.jpg', 'sort_order' => 0]);

        $this->assertStringEndsWith('/storage/products/exact-camera.jpg', $product->firstImageUrl());
    }
}
