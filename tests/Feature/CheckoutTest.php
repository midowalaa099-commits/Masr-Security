<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Models\Order;
use App\Models\Package;
use App\Models\PackageItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\TestWith;
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
        $this->assertSame(0.0, (float) $order->shipping_fee);
        $this->assertSame(2000.0, (float) $order->total);
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

    public function test_checkout_preserves_a_quantity_above_999_when_internal_stock_is_zero(): void
    {
        config(['paymob.secret_key' => null, 'paymob.public_key' => null]);

        $product = Product::factory()->create(['price' => 1, 'stock_quantity' => 0]);

        $this->post(route('cart.add'), [
            'type' => 'product',
            'cartable' => $product->id,
            'quantity' => 1001,
        ])->assertRedirect(route('cart.index'));

        $this->post(route('checkout.store'), [
            'customer_name' => 'Bulk Customer',
            'phone' => '01000000000',
            'email' => 'bulk@example.com',
            'address_line' => '1 Main St',
            'payment_method' => 'cash_on_delivery',
        ])->assertRedirect();

        $order = Order::query()->firstOrFail();

        $this->assertSame(1001, $order->items()->firstOrFail()->quantity);
        $this->assertSame(1001.0, (float) $order->total);
        $this->assertSame(-1001, (int) $product->fresh()->stock_quantity);
    }

    public function test_untracked_stock_stays_null_after_an_order(): void
    {
        $product = Product::factory()->create(['price' => 500, 'stock_quantity' => null]);

        $this->post(route('cart.add'), [
            'type' => 'product',
            'cartable' => $product->id,
            'quantity' => 2,
        ])->assertRedirect(route('cart.index'));

        $this->post(route('checkout.store'), [
            'customer_name' => 'Test User',
            'phone' => '01000000000',
            'payment_method' => 'cash_on_delivery',
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame(0.0, (float) $order->shipping_fee);
        $this->assertSame(1000.0, (float) $order->total);
        $this->assertNull($product->fresh()->stock_quantity);
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

    #[TestWith([false, 1200, null, '1200.00'])]
    #[TestWith([true, 800, null, '800.00'])]
    #[TestWith([false, 1000, 700, '700.00'])]
    public function test_changed_product_prices_require_review_before_ordering(bool $authenticated, int $price, ?int $salePrice, string $expectedPrice): void
    {
        if ($authenticated) {
            $this->actingAs(User::factory()->create());
        }

        $product = Product::factory()->create(['price' => 1000, 'stock_quantity' => 10]);
        $this->post(route('cart.add'), ['type' => 'product', 'cartable' => $product->id, 'quantity' => 2]);
        $this->get(route('checkout.index'))->assertViewHas('total', 2000.0);
        $product->update(['price' => $price, 'sale_price' => $salePrice]);

        $this->post(route('checkout.store'), $this->cashCheckoutData())
            ->assertRedirect(route('checkout.index'))
            ->assertSessionHas('error', __('store.cart_prices_changed'))
            ->assertSessionHasInput('customer_name', 'Price Review Customer');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 10]);
        $this->post(route('checkout.store'), $this->cashCheckoutData())
            ->assertRedirect(route('checkout.index'))->assertSessionHas('error', __('store.cart_prices_changed'));
        $this->assertDatabaseCount('orders', 0);

        $this->get(route('checkout.index'))
            ->assertSee(__('store.cart_prices_changed'))
            ->assertViewHas('total', (float) $expectedPrice * 2);
        $this->post(route('checkout.store'), $this->cashCheckoutData())->assertRedirect();

        $order = Order::query()->sole();
        $this->assertSame($expectedPrice, $order->items()->sole()->unit_price);
        $this->assertSame('0.00', $order->shipping_fee);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 8]);
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_package_price_changes_since_addition_require_review(bool $componentPricing): void
    {
        $component = Product::factory()->component()->create(['price' => 500, 'stock_quantity' => 10]);
        $package = Package::factory()->create(['use_component_pricing' => $componentPricing, 'base_price' => 1000]);
        PackageItem::factory()->for($package)->for($component, 'product')->create(['quantity' => 2]);
        $this->post(route('cart.add'), ['type' => 'package', 'cartable' => $package->id, 'quantity' => 2]);

        if ($componentPricing) {
            $component->update(['sale_price' => 400]);
        } else {
            $package->update(['base_price' => 800]);
        }

        $this->post(route('checkout.store'), $this->cashCheckoutData())
            ->assertRedirect(route('checkout.index'))->assertSessionHas('error', __('store.cart_prices_changed'));

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('products', ['id' => $component->id, 'stock_quantity' => 10]);
        $this->get(route('checkout.index'))->assertViewHas('total', 1600.0);
        $this->post(route('checkout.store'), $this->cashCheckoutData())->assertRedirect();

        $order = Order::query()->sole();
        $this->assertSame('1600.00', $order->total);
        $this->assertSame('800.00', $order->items()->sole()->unit_price);
        $this->assertSame([$component->id => 4], $order->items()->sole()->inventory_snapshot);
        $this->assertDatabaseHas('products', ['id' => $component->id, 'stock_quantity' => 6]);
    }

    public function test_price_changed_as_checkout_reads_products_is_not_silently_charged(): void
    {
        $product = Product::factory()->create(['price' => 1000, 'stock_quantity' => 10]);
        $this->post(route('cart.add'), ['type' => 'product', 'cartable' => $product->id, 'quantity' => 1]);
        $changed = false;
        DB::connection()->beforeExecuting(function (string $query) use ($product, &$changed): void {
            if (! $changed && str_starts_with($query, 'select') && str_contains($query, '"products"')) {
                $changed = true;
                Product::query()->whereKey($product->id)->update(['price' => 1200]);
            }
        });

        $this->post(route('checkout.store'), $this->cashCheckoutData())
            ->assertRedirect(route('checkout.index'))->assertSessionHas('error', __('store.cart_prices_changed'));

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 10]);
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_unavailable_package_or_component_prevents_checkout(bool $deactivateComponent): void
    {
        $component = Product::factory()->create(['price' => 500, 'stock_quantity' => 10]);
        $package = Package::factory()->create();
        PackageItem::factory()->for($package)->for($component, 'product')->create(['quantity' => 1]);
        $this->post(route('cart.add'), ['type' => 'package', 'cartable' => $package->id, 'quantity' => 1]);
        ($deactivateComponent ? $component : $package)->update(['status' => ProductStatus::Inactive]);

        $this->from(route('checkout.index'))->post(route('checkout.store'), $this->cashCheckoutData())
            ->assertRedirect(route('checkout.index'))->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('products', ['id' => $component->id, 'stock_quantity' => 10]);
    }

    public function test_persistent_cart_without_a_session_price_snapshot_requires_review(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->create(['price' => 1000]);
        $cart = $customer->cart()->create();
        $cart->items()->create(['cartable_type' => Product::class, 'cartable_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($customer)->post(route('checkout.store'), $this->cashCheckoutData())
            ->assertRedirect(route('checkout.index'))->assertSessionHas('error', __('store.cart_prices_changed'));

        $this->assertDatabaseCount('orders', 0);
        $this->get(route('checkout.index'))->assertViewHas('total', 1000.0);
        $this->post(route('checkout.store'), $this->cashCheckoutData())->assertRedirect();
        $this->assertDatabaseHas('orders', ['user_id' => $customer->id, 'total' => 1000, 'shipping_fee' => 0]);
    }

    public function test_catalog_price_changes_leave_historical_order_prices_and_shipping_unchanged(): void
    {
        $product = Product::factory()->create(['price' => 1000]);
        $historicalOrder = Order::factory()->create(['subtotal' => 1000, 'shipping_fee' => 60, 'total' => 1060]);
        $historicalOrder->items()->create([
            'orderable_type' => Product::class, 'orderable_id' => $product->id,
            'name_snapshot' => 'Historical product', 'unit_price' => 1000, 'quantity' => 1, 'line_total' => 1000,
        ]);
        $this->post(route('cart.add'), ['type' => 'product', 'cartable' => $product->id, 'quantity' => 1]);
        $product->update(['price' => 1200]);
        $this->get(route('checkout.index'))->assertSee(__('store.cart_prices_changed'));

        $this->post(route('checkout.store'), $this->cashCheckoutData())->assertRedirect();

        $this->assertDatabaseHas('orders', ['id' => $historicalOrder->id, 'subtotal' => 1000, 'shipping_fee' => 60, 'total' => 1060]);
        $this->assertDatabaseHas('order_items', ['order_id' => $historicalOrder->id, 'unit_price' => 1000, 'line_total' => 1000]);
        $this->assertDatabaseHas('orders', ['total' => 1200, 'shipping_fee' => 0]);
    }

    public function test_a_further_price_change_after_review_requires_another_review(): void
    {
        $product = Product::factory()->create(['price' => 1000, 'sale_price' => 800, 'stock_quantity' => 10]);
        $this->post(route('cart.add'), ['type' => 'product', 'cartable' => $product->id, 'quantity' => 1]);
        $product->update(['sale_price' => null]);
        $this->get(route('checkout.index'))->assertSee(__('store.cart_prices_changed'))->assertViewHas('total', 1000.0);
        $product->update(['price' => 1200]);

        $this->post(route('checkout.store'), $this->cashCheckoutData())
            ->assertRedirect(route('checkout.index'))->assertSessionHas('error', __('store.cart_prices_changed'));

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 10]);
    }

    public function test_package_component_quantity_and_discount_changes_use_the_reviewed_total_and_inventory(): void
    {
        $component = Product::factory()->create(['price' => 500, 'stock_quantity' => 0]);
        $package = Package::factory()->create(['discount_amount' => 100]);
        $packageItem = PackageItem::factory()->for($package)->for($component, 'product')->create(['quantity' => 2]);
        $this->post(route('cart.add'), ['type' => 'package', 'cartable' => $package->id, 'quantity' => 1]);
        $packageItem->update(['quantity' => 3]);
        $package->update(['discount_amount' => 200]);

        $this->post(route('checkout.store'), $this->cashCheckoutData())
            ->assertRedirect(route('checkout.index'))->assertSessionHas('error', __('store.cart_prices_changed'));

        $this->assertDatabaseCount('orders', 0);
        $this->get(route('checkout.index'))->assertViewHas('total', 1300.0);
        $this->post(route('checkout.store'), $this->cashCheckoutData())->assertRedirect();
        $this->assertDatabaseHas('orders', ['total' => 1300, 'shipping_fee' => 0]);
        $this->assertDatabaseHas('products', ['id' => $component->id, 'stock_quantity' => -3]);
        $this->assertSame([$component->id => 3], Order::query()->sole()->items()->sole()->inventory_snapshot);
    }

    public function test_a_removed_cart_product_does_not_allow_a_partial_order(): void
    {
        $products = Product::factory()->count(2)->create(['price' => 500, 'stock_quantity' => 10]);
        foreach ($products as $product) {
            $this->post(route('cart.add'), ['type' => 'product', 'cartable' => $product->id, 'quantity' => 1]);
        }
        $products[0]->delete();

        $this->from(route('checkout.index'))->post(route('checkout.store'), $this->cashCheckoutData())
            ->assertRedirect(route('checkout.index'))->assertSessionHas('error', __('store.cart_unavailable_review'));

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('products', ['id' => $products[1]->id, 'stock_quantity' => 10]);
    }

    public function test_checkout_warns_when_unavailable_cart_items_are_excluded(): void
    {
        $products = Product::factory()->count(2)->create(['price' => 500, 'stock_quantity' => 10]);
        foreach ($products as $product) {
            $this->post(route('cart.add'), ['type' => 'product', 'cartable' => $product->id, 'quantity' => 1]);
        }
        $products[0]->delete();

        $this->get(route('checkout.index'))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error', __('store.some_items_unavailable'));
    }

    public function test_checkout_redirects_with_an_unavailable_notice_when_all_cart_items_are_excluded(): void
    {
        $product = Product::factory()->create();
        $this->post(route('cart.add'), ['type' => 'product', 'cartable' => $product->id, 'quantity' => 1]);
        $product->delete();

        $this->get(route('checkout.index'))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error', __('store.some_items_unavailable'));
    }

    /** @return array{customer_name: string, phone: string, payment_method: string} */
    private function cashCheckoutData(): array
    {
        return ['customer_name' => 'Price Review Customer', 'phone' => '01000000000', 'payment_method' => 'cash_on_delivery'];
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
