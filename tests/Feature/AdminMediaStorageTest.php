<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Package;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminMediaStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_replace_a_category_image_without_losing_the_new_upload(): void
    {
        Storage::fake('s3');
        Storage::fake('public');

        $category = Category::factory()->create(['image' => 'categories/old.jpg']);
        Storage::disk('public')->put('categories/old.jpg', 'old image');

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.categories.update', $category), [
                'name_ar' => $category->name_ar,
                'name_en' => $category->name_en,
                'slug' => $category->slug,
                'image' => UploadedFile::fake()->image('new-category.jpg'),
                'remove_image' => '1',
            ])
            ->assertRedirect(route('admin.categories.index'));

        $newImage = $category->fresh()->image;
        $this->assertStringStartsWith('supabase/categories/', $newImage);
        Storage::disk('s3')->assertExists($newImage);
        Storage::disk('public')->assertMissing('categories/old.jpg');
    }

    public function test_admin_can_replace_a_package_cover_without_losing_the_new_upload(): void
    {
        Storage::fake('s3');
        Storage::fake('public');

        $package = Package::factory()->create(['cover_image' => 'packages/old.jpg']);
        Storage::disk('public')->put('packages/old.jpg', 'old cover');

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.packages.update', $package), [
                'name_ar' => $package->name_ar,
                'name_en' => $package->name_en,
                'slug' => $package->slug,
                'status' => $package->status->value,
                'cover_image' => UploadedFile::fake()->image('new-cover.jpg'),
                'remove_cover' => '1',
            ])
            ->assertRedirect(route('admin.packages.edit', $package));

        $newCover = $package->fresh()->cover_image;
        $this->assertStringStartsWith('supabase/packages/', $newCover);
        Storage::disk('s3')->assertExists($newCover);
        Storage::disk('public')->assertMissing('packages/old.jpg');
    }

    public function test_category_and_package_uploads_use_supabase_and_keep_legacy_urls_readable(): void
    {
        Storage::fake('s3', ['url' => 'https://cdn.example.test']);
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'name_ar' => 'فئة اختبار',
                'name_en' => 'Test Category',
                'slug' => 'test-category',
                'image' => UploadedFile::fake()->image('category.png'),
            ])
            ->assertRedirect(route('admin.categories.index'));

        $category = Category::query()->where('slug', 'test-category')->firstOrFail();

        $this->assertStringStartsWith('supabase/categories/', $category->image);
        Storage::disk('s3')->assertExists($category->image);

        $this->post(route('admin.packages.store'), [
            'name_ar' => 'باقة اختبار',
            'name_en' => 'Test Package',
            'slug' => 'test-package',
            'status' => 'active',
            'use_component_pricing' => '0',
            'base_price' => '1500.00',
            'discount_amount' => '0',
            'featured' => '0',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'cover_image' => UploadedFile::fake()->image('package.png'),
        ])->assertRedirect();

        $package = Package::query()->where('slug', 'test-package')->firstOrFail();

        $this->assertStringStartsWith('supabase/packages/', $package->cover_image);
        $this->assertSame(
            'https://cdn.example.test/'.$package->cover_image,
            $package->firstImageUrl(),
        );
        Storage::disk('s3')->assertExists($package->cover_image);

        $legacyPath = 'categories/legacy.png';
        Storage::disk('public')->put($legacyPath, 'legacy image');

        $this->assertSame(
            Storage::disk('public')->url($legacyPath),
            media_url($legacyPath),
        );

        $this->get(route('admin.categories.edit', $category))
            ->assertSee('https://cdn.example.test/'.$category->image);
    }
}
