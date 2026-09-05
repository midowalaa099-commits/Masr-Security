<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name_ar' => $name,
            'name_en' => $name,
            'slug' => Str::slug($name),
            'description_ar' => fake()->paragraph(),
            'description_en' => fake()->paragraph(),
            'base_price' => fake()->randomFloat(2, 1000, 50000),
            'use_component_pricing' => true,
            'discount_amount' => 0,
            'status' => ProductStatus::Active,
            'featured' => false,
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'featured' => true,
        ]);
    }

    public function fixedPricing(): static
    {
        return $this->state(fn (array $attributes) => [
            'use_component_pricing' => false,
        ]);
    }
}
