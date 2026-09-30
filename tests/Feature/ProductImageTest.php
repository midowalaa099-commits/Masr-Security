<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\MediaStorage;
use App\Services\ProductImageStorage;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;
use Throwable;

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
        Storage::fake('s3', ['url' => 'https://cdn.example.test']);
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $file = UploadedFile::fake()->image('camera.jpg', 640, 480);

        $this->actingAs($admin)
            ->post(route('admin.products.images.store', $product), ['image' => $file])
            ->assertRedirect();

        $image = $product->images()->firstOrFail();

        $this->assertSame('supabase', $image->path);
        $this->assertStringStartsWith('supabase/products/', $image->storage_key);
        $this->assertNull($image->content);
        $this->assertStringStartsWith('https://cdn.example.test/supabase/products/', $image->url);
        Storage::disk('s3')->assertExists($image->storage_key);
    }

    public function test_serving_a_product_image_does_not_create_a_customer_cart(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->create();
        $image = $product->images()->create(['path' => 'database', 'sort_order' => 0]);
        $image->content()->create([
            'mime_type' => 'image/png',
            'contents' => base64_encode('image bytes'),
        ]);

        $this->actingAs($customer)
            ->get(route('product-images.show', $image))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $this->assertDatabaseMissing('carts', ['user_id' => $customer->id]);
    }

    public function test_supabase_product_image_uses_the_public_bucket_url(): void
    {
        config()->set('filesystems.disks.s3.url', 'https://example.supabase.co/storage/v1/object/public/masr-media');
        config()->set('filesystems.disks.s3.region', 'eu-central-1');
        config()->set('filesystems.disks.s3.bucket', 'masr-media');
        Storage::forgetDisk('s3');

        $product = Product::factory()->create();
        $image = $product->images()->create([
            'path' => 'supabase',
            'storage_key' => 'supabase/products/camera.jpg',
            'sort_order' => 0,
        ]);

        $this->assertSame(
            'https://example.supabase.co/storage/v1/object/public/masr-media/supabase/products/camera.jpg',
            $image->url,
        );
    }

    public function test_admin_can_create_a_product_with_an_image_and_optional_stock(): void
    {
        Storage::fake('s3');

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
        $image = $product->images()->firstOrFail();

        $this->assertSame('supabase', $image->path);
        Storage::disk('s3')->assertExists($image->storage_key);
    }

    public function test_new_images_follow_the_highest_existing_sort_order(): void
    {
        Storage::fake('s3');

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

    public function test_uploaded_object_is_removed_when_product_image_persistence_fails(): void
    {
        Storage::fake('s3');
        $product = Product::factory()->create();
        $this->rejectProductImageInserts();

        $exception = null;

        try {
            app(ProductImageStorage::class)->store($product, UploadedFile::fake()->image('failed.jpg'));
        } catch (Throwable $caught) {
            $exception = $caught;
        } finally {
            DB::statement('DROP TRIGGER reject_product_image_insert');
        }

        $this->assertInstanceOf(QueryException::class, $exception);
        $this->assertStringContainsString('product image insert rejected', $exception->getMessage());
        $this->assertDatabaseCount('product_images', 0);
        Storage::disk('s3')->assertDirectoryEmpty('supabase');
    }

    public function test_image_persistence_exception_is_preserved_when_object_cleanup_fails(): void
    {
        Storage::fake('s3');
        $product = Product::factory()->create();
        $this->rejectProductImageInserts();

        $storage = $this->partialMock(MediaStorage::class);
        $storage->shouldReceive('store')->passthru();
        $storage->shouldReceive('delete')->once()->andThrow(new RuntimeException('Object cleanup failed.'));
        Log::spy();

        $exception = null;

        try {
            app(ProductImageStorage::class)->store($product, UploadedFile::fake()->image('failed.jpg'));
        } catch (Throwable $caught) {
            $exception = $caught;
        } finally {
            DB::statement('DROP TRIGGER reject_product_image_insert');
        }

        $this->assertInstanceOf(QueryException::class, $exception);
        $this->assertStringContainsString('product image insert rejected', $exception->getMessage());
        Log::shouldHaveReceived('error')->once()->with(
            'product_image.upload_cleanup_failed',
            \Mockery::on(static fn (array $context): bool => str_starts_with($context['storage_key'], 'supabase/products/')),
        );
    }

    private function rejectProductImageInserts(): void
    {
        DB::statement("
            CREATE TRIGGER reject_product_image_insert
            BEFORE INSERT ON product_images
            BEGIN
                SELECT RAISE(ABORT, 'product image insert rejected');
            END
        ");
    }
}
