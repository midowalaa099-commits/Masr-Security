<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\ProductImageStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function test_admin_can_upload_and_serve_an_image_for_an_existing_product(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $file = UploadedFile::fake()->image('camera.jpg', 640, 480);

        $this->actingAs($admin)
            ->post(route('admin.products.images.store', $product), ['image' => $file])
            ->assertRedirect();

        $image = $product->images()->firstOrFail();

        $this->assertSame('database', $image->path);
        $this->assertNotNull($image->content);
        $this->get($image->url)->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_admin_can_create_a_product_with_an_image_and_optional_stock(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'sku' => 'CAMERA-NEW-1',
            'name_en' => 'New Camera',
            'name_ar' => 'كاميرا جديدة',
            'slug' => '',
            'price' => '1500.00',
            'low_stock_threshold' => 5,
            'status' => 'active',
            'type' => 'simple',
            'images' => [UploadedFile::fake()->image('new-camera.png', 400, 300)],
        ])->assertRedirect();

        $product = Product::query()->where('sku', 'CAMERA-NEW-1')->firstOrFail();

        $this->assertNull($product->stock_quantity);
        $this->assertSame('camera-new-1', $product->slug);
        $this->assertSame('database', $product->images()->firstOrFail()->path);
        $this->get($product->firstImageUrl())->assertOk()->assertHeader('Content-Type', 'image/png');
    }

    public function test_new_images_follow_the_highest_existing_sort_order(): void
    {
        $product = Product::factory()->create();
        $product->images()->create(['path' => 'products/first.jpg', 'sort_order' => 0]);
        $product->images()->create(['path' => 'products/third.jpg', 'sort_order' => 2]);

        $storage = app(ProductImageStorage::class);
        $storage->storeMany($product, [
            UploadedFile::fake()->image('fourth.jpg'),
            UploadedFile::fake()->image('fifth.jpg'),
        ]);
        $lastImage = $storage->store($product, UploadedFile::fake()->image('sixth.jpg'));

        $this->assertSame([0, 2, 3, 4, 5], $product->images()->orderBy('sort_order')->pluck('sort_order')->all());
        $this->assertSame(5, $lastImage->sort_order);

        $emptyProduct = Product::factory()->create();
        $this->assertSame(0, $storage->store($emptyProduct, UploadedFile::fake()->image('first.jpg'))->sort_order);
    }
}
