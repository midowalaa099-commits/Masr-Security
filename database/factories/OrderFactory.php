<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 500, 20000);
        $shippingFee = fake()->randomElement([0, 0, 50, 100]);

        return [
            'user_id' => User::factory(),
            'order_number' => 'MSR-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'customer_name' => fake()->name(),
            'phone' => fake()->numerify('01########'),
            'email' => fake()->safeEmail(),
            'governorate' => fake()->randomElement(['Cairo', 'Giza', 'Alexandria', 'Mansoura']),
            'city' => fake()->city(),
            'address_line' => fake()->streetAddress(),
            'notes' => null,
            'subtotal' => $subtotal,
            'shipping_fee' => $shippingFee,
            'total' => round($subtotal + $shippingFee, 2),
            'status' => OrderStatus::Pending,
            'payment_method' => 'card',
            'tracking_number' => null,
            'admin_note' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Paid,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Cancelled,
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Delivered,
        ]);
    }
}
