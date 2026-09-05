<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Package;
use App\Models\PackageItem;
use App\Models\Payment;
use App\Models\PaymentCallbackResult;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionAuditTest extends TestCase
{
    use RefreshDatabase;

    private function shippingFee(): void
    {
        Setting::firstOrCreate(['key' => 'shipping_fee'], ['value' => '60']);
    }

    private function addToCart(string $type, int $id, int $quantity = 1): void
    {
        $this->post(route('cart.add'), [
            'type' => $type,
            'cartable' => $id,
            'quantity' => $quantity,
        ])->assertRedirect(route('cart.index'));
    }

    private function createProduct(float $price = 1000, int $stock = 10): Product
    {
        return Product::factory()->create(['price' => $price, 'stock_quantity' => $stock]);
    }

    private function placeOrderThroughCheckout(array $overrides = []): Order
    {
        $this->shippingFee();

        $this->post(route('checkout.store'), array_merge([
            'customer_name' => 'Test User',
            'phone' => '01000000000',
            'email' => 'test@example.com',
            'payment_method' => 'card',
        ], $overrides));

        return Order::firstOrFail();
    }

    private function orderWithItem(Product $product, int $quantity = 2, array $status = []): Order
    {
        $order = Order::factory()->create(array_merge([
            'status' => OrderStatus::Pending,
        ], $status));

        $order->items()->create([
            'orderable_type' => Product::class,
            'orderable_id' => $product->id,
            'name_snapshot' => $product->name_en,
            'sku_snapshot' => $product->sku,
            'inventory_snapshot' => [$product->id => $quantity],
            'unit_price' => $product->price,
            'quantity' => $quantity,
            'line_total' => (float) $product->price * $quantity,
        ]);

        return $order;
    }

    // ------------------------------------------------------------ cart/auth

    public function test_authenticated_customer_adds_to_a_persistent_database_cart(): void
    {
        $product = $this->createProduct();
        $customer = User::factory()->create();

        $this->actingAs($customer)->post(route('cart.add'), [
            'type' => 'product',
            'cartable' => $product->id,
            'quantity' => 2,
        ])->assertRedirect(route('cart.index'));

        $cart = Cart::query()->where('user_id', $customer->id)->firstOrFail();

        $this->assertSame(1, (int) $cart->items()->count());
        $this->assertSame(2, (int) $cart->items()->first()->quantity);

        $this->actingAs($customer)->get(route('cart.index'))->assertOk()->assertSee('2,000.00');
    }

    public function test_login_merges_the_guest_session_cart_into_the_account(): void
    {
        $this->shippingFee();
        $this->createProduct(1000, 10);

        $this->post(route('cart.add'), ['type' => 'product', 'cartable' => 1, 'quantity' => 2]);

        $customer = User::factory()->create(['email' => 'merge@example.com', 'password' => 'password']);

        $this->post(route('login'), ['email' => 'merge@example.com', 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $cart = Cart::query()->where('user_id', $customer->id)->firstOrFail();

        $this->assertSame(2, (int) $cart->items()->firstOrFail()->quantity);

        $this->actingAs($customer)->post(route('cart.add'), ['type' => 'product', 'cartable' => 1, 'quantity' => 1]);
        $this->assertSame(3, (int) $cart->fresh()->items()->firstOrFail()->quantity);
    }

    public function test_registration_merges_the_guest_session_cart(): void
    {
        $this->createProduct(1000, 10);

        $this->post(route('cart.add'), ['type' => 'product', 'cartable' => 1, 'quantity' => 2]);

        $this->post(route('register'), [
            'name' => 'New Customer',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('dashboard', absolute: false));

        $user = User::query()->where('email', 'new@example.com')->firstOrFail();

        $this->assertSame(1, (int) Cart::query()->where('user_id', $user->id)->firstOrFail()->items()->count());
    }

    // ------------------------------------------------------- authorization

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $customer = User::factory()->create();
        $other = User::factory()->create();

        $order = $this->orderWithItem($this->createProduct(), 1, ['user_id' => $customer->id]);

        $this->actingAs($other)->get(route('account.orders.show', $order))->assertForbidden();
    }

    public function test_guest_is_redirected_away_from_account_routes(): void
    {
        $this->get(route('account.orders'))->assertRedirect(route('login'));
    }

    public function test_customer_is_rejected_from_admin_routes_server_side(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get(route('admin.orders.index'))->assertForbidden();
        $this->actingAs($customer)->post(route('admin.products.store'), [])->assertForbidden();
    }

    // -------------------------------------------------------- checkout money

    public function test_frontend_submitted_totals_are_ignored(): void
    {
        $this->shippingFee();
        $this->createProduct(1000, 10);
        $this->addToCart('product', 1, 2);

        $this->post(route('checkout.store'), [
            'customer_name' => 'Test User',
            'phone' => '01000000000',
            'payment_method' => 'card',
            'subtotal' => 0.01,
            'shipping_fee' => 0,
            'total' => 0.01,
        ])->assertRedirect();

        $order = Order::firstOrFail();

        $this->assertSame(2000.0, (float) $order->subtotal);
        $this->assertSame(60.0, (float) $order->shipping_fee);
        $this->assertSame(2060.0, (float) $order->total);
    }

    public function test_empty_cart_and_invalid_inputs_are_rejected(): void
    {
        $this->post(route('checkout.store'), [
            'customer_name' => 'Test',
            'phone' => '01000000000',
            'payment_method' => 'card',
        ])->assertRedirect(route('cart.index'));

        $this->post(route('cart.add'), ['type' => 'product', 'cartable' => 99999, 'quantity' => 1])
            ->assertNotFound();

        $this->createProduct();

        $this->post(route('cart.add'), ['type' => 'product', 'cartable' => 1, 'quantity' => 0])
            ->assertSessionHasErrors('quantity');

        $this->post(route('cart.add'), ['type' => 'product', 'cartable' => 1, 'quantity' => -3])
            ->assertSessionHasErrors('quantity');
    }

    public function test_inactive_product_and_package_cannot_be_added_to_the_cart(): void
    {
        Product::factory()->create(['status' => ProductStatus::Inactive]);

        $this->post(route('cart.add'), ['type' => 'product', 'cartable' => 1, 'quantity' => 1])
            ->assertNotFound();

        $package = Package::factory()->create(['status' => ProductStatus::Inactive]);

        $this->post(route('cart.add'), ['type' => 'package', 'cartable' => $package->id, 'quantity' => 1])
            ->assertNotFound();
    }

    // --------------------------------------------------------------- stock

    public function test_checkout_rejects_a_product_that_ran_out_between_add_and_checkout(): void
    {
        $product = $this->createProduct(1000, 2);
        $this->addToCart('product', $product->id, 2);

        $product->update(['stock_quantity' => 0]);

        $this->from(route('checkout.index'))
            ->post(route('checkout.store'), [
                'customer_name' => 'Test User',
                'phone' => '01000000000',
                'payment_method' => 'card',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, Order::count());
    }

    public function test_checkout_rejects_a_package_whose_component_stock_was_depleted(): void
    {
        $componentA = Product::factory()->component()->create(['price' => 500, 'stock_quantity' => 4]);
        Product::factory()->component()->create(['price' => 300, 'stock_quantity' => 10]);

        $package = Package::factory()->create(['use_component_pricing' => true, 'discount_amount' => 0]);

        $package->items()->saveMany([
            new PackageItem(['product_id' => $componentA->id, 'quantity' => 2]),
            new PackageItem(['product_id' => 2, 'quantity' => 1]),
        ]);

        $this->addToCart('package', $package->id, 2);

        $componentA->update(['stock_quantity' => 2]);

        $this->from(route('checkout.index'))
            ->post(route('checkout.store'), [
                'customer_name' => 'Test User',
                'phone' => '01000000000',
                'payment_method' => 'card',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, Order::count());
    }

    public function test_package_availability_is_bounded_by_its_components(): void
    {
        $componentA = Product::factory()->component()->create(['price' => 500, 'stock_quantity' => 5]);
        $componentB = Product::factory()->component()->create(['price' => 300, 'stock_quantity' => 5]);

        $package = Package::factory()->create();
        $package->items()->saveMany([
            new PackageItem(['product_id' => $componentA->id, 'quantity' => 2]),
            new PackageItem(['product_id' => $componentB->id, 'quantity' => 1]),
        ]);

        $package->load('items.product');

        $this->assertSame(2, $package->availableQuantity());

        $this->post(route('cart.add'), [
            'type' => 'package',
            'cartable' => $package->id,
            'quantity' => 999,
        ])->assertRedirect()
            ->assertSessionHas('error');

        $this->addToCart('package', $package->id, 2);

        $this->get(route('cart.index'))->assertSee('2,600.00');
    }

    public function test_order_item_snapshots_preserve_price_sku_and_inventory(): void
    {
        $componentA = Product::factory()->component()->create(['price' => 500, 'stock_quantity' => 10]);
        $componentB = Product::factory()->component()->create(['price' => 300, 'stock_quantity' => 10]);

        $package = Package::factory()->create(['use_component_pricing' => true, 'discount_amount' => 100]);
        $package->items()->saveMany([
            new PackageItem(['product_id' => $componentA->id, 'quantity' => 2]),
            new PackageItem(['product_id' => $componentB->id, 'quantity' => 1]),
        ]);

        $this->shippingFee();
        $this->addToCart('package', $package->id, 1);

        $this->post(route('checkout.store'), [
            'customer_name' => 'Test User',
            'phone' => '01000000000',
            'payment_method' => 'card',
        ]);

        $order = Order::firstOrFail();
        $orderItem = $order->items()->firstOrFail();

        $this->assertSame($package->purchasableName(), $orderItem->name_snapshot);
        $this->assertNull($orderItem->sku_snapshot);
        $this->assertSame([$componentA->id => 2, $componentB->id => 1], $orderItem->inventory_snapshot);
        $this->assertSame(1200.0, (float) $orderItem->unit_price);
        $this->assertSame(1, (int) $orderItem->quantity);
        $this->assertSame(1200.0, (float) $orderItem->line_total);
    }

    // -------------------------------------------------------------- payments

    public function test_a_finalized_payment_is_never_downgraded_by_a_late_callback(): void
    {
        $this->shippingFee();
        $this->createProduct(1000, 10);
        $this->addToCart('product', 1, 2);

        $order = $this->placeOrderThroughCheckout();
        $payment = Order::firstOrFail()->payments()->firstOrFail();

        $service = app(PaymentService::class);

        $success = new PaymentCallbackResult(
            payment: $payment,
            status: PaymentStatus::Success,
            transactionReference: 'TXN-SUCCESS',
            providerTransactionId: 'TXN-SUCCESS',
            providerOrderId: null,
            raw: ['sandbox' => true],
        );

        $service->applyResult($success);

        $this->assertSame(PaymentStatus::Success, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);

        // A duplicate success webhook must not reprocess the payment.
        $service->applyResult($success);
        $this->assertSame(PaymentStatus::Success, $payment->fresh()->status);

        // A late conflicting "failed" callback must not downgrade a paid order.
        $lateFailure = new PaymentCallbackResult(
            payment: $payment,
            status: PaymentStatus::Failed,
            transactionReference: 'TXN-LATE-FAIL',
            providerTransactionId: 'TXN-LATE-FAIL',
            providerOrderId: null,
            raw: ['sandbox' => true],
        );

        $service->applyResult($lateFailure);

        $this->assertSame(PaymentStatus::Success, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
    }

    public function test_webhook_with_a_wrong_amount_cannot_mark_an_order_paid(): void
    {
        config(['paymob.hmac_secret' => 'test-secret']);

        $order = $this->orderWithItem($this->createProduct(), 1);
        $payment = $order->payments()->save(Payment::factory()->for($order)->create([
            'paymob_transaction_id' => 'TXN-100',
            'amount' => 1000,
            'status' => PaymentStatus::Pending,
        ]));

        $obj = [
            'amount_cents' => 99999,
            'id' => 'TXN-100',
            'success' => true,
            'pending' => false,
        ];

        $hmac = $this->hmacFor($obj);

        $this->postJson('/api/paymob/webhook?hmac='.urlencode($hmac), [
            'obj' => $obj,
        ])->assertOk()->assertJson(['received' => true]);

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_webhook_with_an_invalid_signature_is_acknowledged_but_ignored(): void
    {
        config(['paymob.hmac_secret' => 'test-secret']);

        $order = $this->orderWithItem($this->createProduct(), 1);
        $payment = $order->payments()->save(Payment::factory()->for($order)->create([
            'paymob_transaction_id' => 'TXN-101',
            'amount' => 1000,
            'status' => PaymentStatus::Pending,
        ]));

        $obj = ['id' => 'TXN-101', 'success' => true, 'pending' => false];

        $this->postJson('/api/paymob/webhook?hmac=forged', ['obj' => $obj])->assertOk();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_sandbox_simulation_is_off_when_paymob_credentials_are_configured(): void
    {
        config([
            'paymob.secret_key' => 'secret',
            'paymob.public_key' => 'public',
            'paymob.sandbox_mode' => false,
        ]);

        $order = $this->orderWithItem($this->createProduct(), 1);
        $payment = $order->payments()->save(Payment::factory()->for($order)->create());

        $this->get(route('payments.sandbox', $payment))->assertNotFound();
        $this->post(route('payments.sandbox.complete', $payment), ['result' => 'success'])->assertNotFound();
    }

    public function test_sandbox_cannot_finalize_an_already_finalized_payment(): void
    {
        $this->shippingFee();
        $this->createProduct(1000, 10);
        $this->addToCart('product', 1, 2);

        $order = $this->placeOrderThroughCheckout();
        $payment = $order->payments()->firstOrFail();

        $this->post(route('payments.sandbox.complete', $payment), ['result' => 'success'])->assertRedirect();

        $this->post(route('payments.sandbox.complete', $payment->fresh()), ['result' => 'success'])
            ->assertStatus(422);
    }

    public function test_a_failed_payment_keeps_the_stock_reserved(): void
    {
        $product = $this->createProduct(1000, 10);
        $this->addToCart('product', $product->id, 2);

        $order = $this->placeOrderThroughCheckout();
        $payment = $order->payments()->firstOrFail();

        $this->post(route('payments.sandbox.complete', $payment), ['result' => 'failed'])->assertRedirect();

        $this->assertSame(OrderStatus::AwaitingPayment, $order->fresh()->status);
        $this->assertSame(8, (int) $product->fresh()->stock_quantity);
    }

    // --------------------------------------------------------------- admin

    public function test_product_referenced_by_a_package_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();

        $component = Product::factory()->component()->create();

        $package = Package::factory()->create();
        $package->items()->save(new PackageItem(['product_id' => $component->id, 'quantity' => 1]));

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $component))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('products', ['id' => $component->id]);
        $this->assertDatabaseHas('package_items', ['product_id' => $component->id]);
    }

    public function test_product_with_order_history_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();

        $product = $this->createProduct();

        $this->orderWithItem($product, 1);

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertSame(1, OrderItem::query()->where('orderable_id', $product->id)->count());
    }

    public function test_deleting_an_unused_package_is_allowed(): void
    {
        $admin = User::factory()->admin()->create();

        $package = Package::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.packages.destroy', $package))
            ->assertRedirect(route('admin.packages.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('packages', ['id' => $package->id]);
    }

    public function test_deleting_a_package_referenced_by_an_order_is_blocked(): void
    {
        $admin = User::factory()->admin()->create();

        $package = Package::factory()->create(['base_price' => 1500, 'use_component_pricing' => false]);

        $order = Order::factory()->create(['status' => OrderStatus::Paid]);

        $order->items()->create([
            'orderable_type' => Package::class,
            'orderable_id' => $package->id,
            'name_snapshot' => $package->name_en,
            'sku_snapshot' => null,
            'inventory_snapshot' => null,
            'unit_price' => 1500,
            'quantity' => 1,
            'line_total' => 1500,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.packages.destroy', $package))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('packages', ['id' => $package->id]);
    }

    public function test_historical_order_items_remain_intact_when_deletion_is_blocked(): void
    {
        $admin = User::factory()->admin()->create();

        $package = Package::factory()->create(['base_price' => 1500, 'use_component_pricing' => false]);

        $order = Order::factory()->create(['status' => OrderStatus::Paid]);

        $item = $order->items()->create([
            'orderable_type' => Package::class,
            'orderable_id' => $package->id,
            'name_snapshot' => $package->name_en,
            'sku_snapshot' => null,
            'inventory_snapshot' => null,
            'unit_price' => 1500,
            'quantity' => 2,
            'line_total' => 3000,
        ]);

        $this->actingAs($admin)->delete(route('admin.packages.destroy', $package));

        $item->refresh();

        $this->assertSame($package->name_en, $item->name_snapshot);
        $this->assertSame('1500.00', $item->unit_price);
        $this->assertSame(2, (int) $item->quantity);
        $this->assertSame('3000.00', $item->line_total);
        $this->assertDatabaseHas('order_items', ['id' => $item->id]);
    }

    public function test_deactivating_a_package_still_works(): void
    {
        $admin = User::factory()->admin()->create();

        $package = Package::factory()->create(['status' => ProductStatus::Active]);

        $this->actingAs($admin)
            ->put(route('admin.packages.update', $package), [
                'name_ar' => $package->name_ar,
                'name_en' => $package->name_en,
                'slug' => $package->slug,
                'status' => ProductStatus::Inactive->value,
                'use_component_pricing' => true,
                'discount_amount' => 0,
                'featured' => false,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(ProductStatus::Inactive, $package->fresh()->status);
        $this->assertDatabaseHas('packages', ['id' => $package->id]);
    }

    public function test_cancelling_and_reopening_an_order_keeps_stock_consistent(): void
    {
        $admin = User::factory()->admin()->create();

        $product = $this->createProduct(1000, 5);

        $order = $this->orderWithItem($product, 2);
        $order->payments()->save(Payment::factory()->successful()->for($order)->create());

        $this->actingAs($admin)
            ->post(route('admin.orders.status', $order), ['status' => OrderStatus::Cancelled->value])
            ->assertRedirect();

        $this->assertSame(7, (int) $product->fresh()->stock_quantity);

        $this->actingAs($admin)
            ->post(route('admin.orders.status', $order->fresh()), ['status' => OrderStatus::Paid->value])
            ->assertRedirect();

        $this->assertSame(5, (int) $product->fresh()->stock_quantity);
    }

    public function test_deleting_a_category_does_not_delete_its_products(): void
    {
        $admin = User::factory()->admin()->create();

        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->actingAs($admin)->delete(route('admin.categories.destroy', $category))->assertRedirect();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'category_id' => null]);
    }

    // ------------------------------------------------------------- helpers

    /**
     * @param  array<string, mixed>  $obj
     */
    private function hmacFor(array $obj): string
    {
        $fields = [
            'amount_cents', 'created_at', 'currency', 'error_occured', 'has_parent_transaction',
            'id', 'integration_id', 'is_3d_secure', 'is_auth', 'is_capture',
            'is_refunded', 'is_standalone_payment', 'is_voided', 'order.id', 'owner',
            'pending', 'source_data.pan', 'source_data.sub_type', 'source_data.type', 'success',
        ];

        $concatenated = '';

        foreach ($fields as $field) {
            $concatenated .= $this->stringify(data_get($obj, $field));
        }

        return hash_hmac('sha512', $concatenated, (string) config('paymob.hmac_secret'));
    }

    private function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }
}
