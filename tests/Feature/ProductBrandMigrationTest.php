<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductBrandMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_canonical_brand_spelling_wins_over_existing_case_variants(): void
    {
        Product::factory()->create(['brand' => 'hIKVISION']);

        Schema::drop('product_brands');

        $migration = require database_path('migrations/2026_09_29_165640_create_product_brands_table.php');
        $migration->up();

        $this->assertDatabaseHas('product_brands', ['name' => 'Hikvision']);
        $this->assertDatabaseMissing('product_brands', ['name' => 'hIKVISION']);
    }
}
