<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductSpec;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductSpec>
 */
class ProductSpecFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $keys = ['Resolution', 'Night Vision', 'Lens', 'Weather Rating', 'Storage', 'Power'];

        return [
            'product_id' => Product::factory(),
            'spec_key' => fake()->randomElement($keys),
            'spec_value_ar' => fake()->word(),
            'spec_value_en' => fake()->word(),
            'sort_order' => 0,
        ];
    }
}
