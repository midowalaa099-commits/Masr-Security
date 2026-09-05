<?php

namespace Database\Factories;

use App\Models\Package;
use App\Models\PackageItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PackageItem>
 */
class PackageItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'package_id' => Package::factory(),
            'product_id' => Product::factory()->component(),
            'quantity' => fake()->numberBetween(1, 4),
        ];
    }
}
