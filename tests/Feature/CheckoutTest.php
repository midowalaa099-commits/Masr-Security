<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function cartWithProduct(float $price, int $stock, int $quantity): void
    {
        Product::factory()->create(['price' => $price, 'stock_quantity' => $stock]);

        $this->post(route('cart.add'), [
            'type' => 'product',
            'cartable' => 1,
            'quantity' => $quantity,
        ]);
    }

    public function test_checkout_requires_items_in_the_cart(): void
    {
        $this->get(route('checkout.index'))->assertRedirect(route('cart.index'));

        $this->post(route('checkout.store'), [
            'customer_name' => 'Test User',
            'phone' => '01000000000',
            'payment_method' => 'card',
        ])->assertRedirect(route('cart.index'));
    }

    public function test_guest_can_place_an_order_and_reach_the_sandbox(): void
    {
        Setting::create(['key' => 'shipping_fee', 'value' => '60']);

        $this->cartWithProduct(1000, 10, 2);

        $this->post(route('checkout.store'), [
            'customer_name' => 'Test User',
            'phone' => '01000000000',
            'email' => 'test@example.com',
            'governorate' => 'Cairo',
            'city' => 'Nasr City',
            'address_line' => '1 Main St',
            'payment_method' => 'card',
        ])->assertStatus(302);

        $order = Order::firstOrFail();

        $this->assertMatchesRegularExpression('/^MSR-\d{8}-[A-Z0-9]{6}$/', $order->order_number);
        $this->assertSame(2000.0, (float) $order->subtotal);
        $this->assertSame(60.0, (float) $order->shipping_fee);
        $this->assertSame(2060.0, (float) $order->total);
        $this->assertSame(OrderStatus::AwaitingPayment, $order->status);
        $this->assertNull($order->user_id);

        $this->assertSame(8, (int) Product::findOrFail(1)->stock_quantity);
        $this->assertSame([], session('cart.items', []));

        $payment = Payment::where('order_id', $order->id)->firstOrFail();
        $this->assertSame(PaymentStatus::Pending, $payment->status);
    }

    public function test_guest_can_place_a_cash_on_delivery_order_without_a_payment_gateway(): void
    {
        config([
            'paymob.secret_key' => null,
            'paymob.public_key' => null,
            'paymob.sandbox_mode' => false,
        ]);

        $this->cartWithProduct(1000, 10, 1);

        $this->post(route('checkout.store'), [
            'customer_name' => 'Cash Customer',
            'phone' => '01000000000',
            'email' => 'cash@example.com',
            'address_line' => '1 Main St',
            'payment_method' => 'cash_on_delivery',
        ])->assertRedirect();

        $order = Order::firstOrFail();

        $this->assertSame('cash_on_delivery', $order->payment_method);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(0, $order->payments()->count());
        $this->get(route('checkout.success', $order))
            ->assertOk()
            ->assertSee(__('payments.cash_on_delivery_confirmed'));
    }

    public function test_unconfigured_online_payment_is_rejected_before_creating_an_order(): void
    {
        config([
            'paymob.secret_key' => null,
            'paymob.public_key' => null,
            'paymob.sandbox_mode' => false,
        ]);

        $this->cartWithProduct(1000, 10, 1);

        $this->from(route('checkout.index'))->post(route('checkout.store'), [
            'customer_name' => 'Card Customer',
            'phone' => '01000000000',
            'payment_method' => 'card',
        ])->assertRedirect(route('checkout.index'))
            ->assertSessionHas('error');

        $this->assertSame(0, Order::count());
        $this->assertSame(1, session('cart.items.0.quantity'));
    }

    public function test_simulating_a_successful_payment_marks_the_order_paid(): void
    {
        $this->cartWithProduct(1000, 10, 2);

        $this->post(route('checkout.store'), [
            'customer_name' => 'Test User',
            'phone' => '01000000000',
            'payment_method' => 'card',
        ]);

        $order = Order::firstOrFail();
        $payment = $order->payments()->firstOrFail();

        $this->post(route('payments.sandbox.complete', $payment), [
            'result' => 'success',
        ])->assertRedirect(route('checkout.success', $order));

        $this->assertSame(PaymentStatus::Success, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);

        $this->get(route('checkout.success', $order))->assertOk()->assertSee($order->order_number);
    }

    public function test_simulating_a_failed_payment_keeps_the_order_awaiting_payment(): void
    {
        $this->cartWithProduct(1000, 10, 2);

        $this->post(route('checkout.store'), [
            'customer_name' => 'Test User',
            'phone' => '01000000000',
            'payment_method' => 'card',
        ]);

        $order = Order::firstOrFail();
        $payment = $order->payments()->firstOrFail();

        $this->post(route('payments.sandbox.complete', $payment), [
            'result' => 'failed',
        ])->assertRedirect(route('payments.sandbox', $payment));

        $this->assertSame(PaymentStatus::Failed, $payment->fresh()->status);
        $this->assertSame(OrderStatus::AwaitingPayment, $order->fresh()->status);
    }

    public function test_guest_cannot_view_another_order_success_page(): void
    {
        $order = $this->createOrderForUser(null);

        $this->get(route('checkout.success', $order))->assertNotFound();
    }

    public function test_customer_can_view_their_own_order_success_page_only(): void
    {
        $customer = User::factory()->create();
        $other = User::factory()->create();

        $order = $this->createOrderForUser($customer);

        $this->actingAs($customer)->get(route('checkout.success', $order))->assertOk();
        $this->actingAs($other)->get(route('checkout.success', $order))->assertNotFound();
    }

    private function createOrderForUser(?User $user): Order
    {
        $order = Order::factory()->create([
            'user_id' => $user?->id,
            'status' => OrderStatus::Paid,
        ]);

        $order->payments()->save(Payment::factory()->successful()->for($order)->create());

        return $order;
    }
}
