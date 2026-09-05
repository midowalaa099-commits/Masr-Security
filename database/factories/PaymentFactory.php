<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $order = Order::factory()->create();

        return [
            'order_id' => $order->id,
            'provider' => 'paymob',
            'paymob_order_id' => fake()->numerify('############'),
            'paymob_transaction_id' => fake()->numerify('############'),
            'method' => PaymentMethod::Card,
            'amount' => $order->total,
            'status' => PaymentStatus::Pending,
            'transaction_reference' => null,
            'raw_response' => null,
            'paid_at' => null,
        ];
    }

    public function successful(): static
    {
        return $this
            ->afterCreating(function (Payment $payment) {
                $order = $payment->order;

                if ($order !== null && in_array($order->status, [OrderStatus::Pending, OrderStatus::AwaitingPayment], true)) {
                    $order->update(['status' => OrderStatus::Paid]);
                }
            })
            ->state(fn (array $attributes) => [
                'status' => PaymentStatus::Success,
                'transaction_reference' => fake()->bothify('TXN-####-####'),
                'paid_at' => now(),
            ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Failed,
        ]);
    }
}
