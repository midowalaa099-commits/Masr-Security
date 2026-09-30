<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Package;
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
}
