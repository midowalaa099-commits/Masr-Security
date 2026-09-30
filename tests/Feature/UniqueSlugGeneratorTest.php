<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Package;
use App\Models\Product;
use App\Services\UniqueSlugGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UniqueSlugGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_consistent_unique_slugs_for_catalog_models(): void
    {
        Category::factory()->create(['slug' => 'network']);
        Package::factory()->create(['slug' => 'camera-kit']);
        Product::factory()->create(['slug' => 'camera-x']);

        $generator = app(UniqueSlugGenerator::class);

        $this->assertSame('network-2', $generator->generate('Network', 'category', new Category));
        $this->assertSame('camera-kit-2', $generator->generate('Camera Kit', 'package', new Package));
        $this->assertSame('camera-x-2', $generator->generate('Camera X', 'product', new Product));
        $this->assertSame('category', $generator->generate('---', 'category', new Category));
    }

    public function test_it_does_not_treat_the_current_model_slug_as_a_collision(): void
    {
        $category = Category::factory()->create(['slug' => 'network']);

        $slug = app(UniqueSlugGenerator::class)->generate('Network', 'category', $category, $category);

        $this->assertSame('network', $slug);
    }
}
