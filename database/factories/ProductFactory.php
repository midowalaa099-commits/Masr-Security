<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
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
            'category_id' => Category::factory(),
            'sku' => 'HIK-'.strtoupper(Str::random(8)),
            'name_ar' => $name,
            'name_en' => $name,
            'slug' => Str::slug($name),
            'description_ar' => fake()->paragraph(),
            'description_en' => fake()->paragraph(),
            'brand' => fake()->randomElement(['Hikvision', 'Dahua', 'Uniview']),
            'model_number' => fake()->bothify('DS-####-###'),
            'price' => fake()->randomFloat(2, 500, 20000),
            'sale_price' => null,
            'stock_quantity' => fake()->numberBetween(0, 100),
            'low_stock_threshold' => 5,
            'status' => ProductStatus::Active,
            'type' => ProductType::Simple,
            'featured' => false,
        ];
    }

    public function component(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductType::Component,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 0,
        ]);
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => fake()->numberBetween(1, 5),
        ]);
    }

    public function onSale(): static
    {
        return $this->state(fn (array $attributes) => [
            'sale_price' => round(fake()->randomFloat(2, 100, (float) ($attributes['price'] ?? 1000) - 1), 2),
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'featured' => true,
        ]);
    }
}
