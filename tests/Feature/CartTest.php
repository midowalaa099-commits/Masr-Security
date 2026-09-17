<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Package;
use App\Models\PackageItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_add_a_product_and_see_the_total(): void
    {
        Product::factory()->create(['category_id' => Category::factory(), 'price' => 1000, 'stock_quantity' => 10]);

        $this->post(route('cart.add'), [
            'type' => 'product',
            'cartable' => 1,
            'quantity' => 2,
        ])->assertRedirect(route('cart.index'));

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('2,000.00');
    }

    public function test_guest_can_order_more_than_the_private_stock_count(): void
    {
        Product::factory()->create(['price' => 1000, 'stock_quantity' => 10]);

        $this->post(route('cart.add'), [
            'type' => 'product',
            'cartable' => 1,
            'quantity' => 12,
        ])->assertRedirect(route('cart.index'));

        $this->get(route('cart.index'))->assertOk()->assertSee('12,000.00')->assertDontSee(__('store.out_of_stock'));
    }

    public function test_guest_can_update_and_remove_a_line(): void
    {
        Product::factory()->create(['price' => 1000, 'stock_quantity' => 10]);

        $this->post(route('cart.add'), ['type' => 'product', 'cartable' => 1, 'quantity' => 2]);

        $this->patch(route('cart.update', ['type' => 'product', 'cartable' => 1]), ['quantity' => 5])
            ->assertRedirect();

        $this->get(route('cart.index'))->assertOk()->assertSee('5,000.00');

        $this->delete(route('cart.remove', ['type' => 'product', 'cartable' => 1]))->assertRedirect();

        $this->get(route('cart.index'))->assertOk()->assertSee(__('store.cart_empty'));
    }

    public function test_guest_can_clear_the_cart(): void
    {
        Product::factory()->create(['price' => 1000, 'stock_quantity' => 10]);

        $this->post(route('cart.add'), ['type' => 'product', 'cartable' => 1, 'quantity' => 2]);
        $this->post(route('cart.clear'))->assertRedirect();

        $this->get(route('cart.index'))->assertOk()->assertSee(__('store.cart_empty'));
    }

    public function test_package_line_uses_component_pricing_and_discount(): void
    {
        $componentA = Product::factory()->component()->create(['price' => 500, 'stock_quantity' => 10]);
        $componentB = Product::factory()->component()->create(['price' => 300, 'stock_quantity' => 10]);

        $package = Package::factory()->create(['use_component_pricing' => true, 'discount_amount' => 100]);

        $package->items()->saveMany([
            new PackageItem(['product_id' => $componentA->id, 'quantity' => 1]),
            new PackageItem(['product_id' => $componentB->id, 'quantity' => 1]),
        ]);

        $this->post(route('cart.add'), [
            'type' => 'package',
            'cartable' => $package->id,
            'quantity' => 1,
        ])->assertRedirect(route('cart.index'));

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('700.00')
            ->assertSee('100.00');
    }

    public function test_cannot_add_an_inactive_product(): void
    {
        Product::factory()->create(['price' => 1000, 'stock_quantity' => 10, 'status' => ProductStatus::Inactive]);

        $this->post(route('cart.add'), [
            'type' => 'product',
            'cartable' => 1,
            'quantity' => 1,
        ])->assertNotFound();
    }

    public function test_guest_and_customer_carts_preserve_quantities_above_999(): void
    {
        $product = Product::factory()->create(['price' => 1, 'stock_quantity' => 0]);
        $user = User::factory()->create();

        $this->post(route('cart.add'), [
            'type' => 'product',
            'cartable' => $product->id,
            'quantity' => 1000,
        ])->assertRedirect(route('cart.index'));

        $this->assertSame(1000, app(CartService::class)->rawEntries()[0]['quantity']);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);

        $this->assertSame(1000, app(CartService::class)->rawEntries()[0]['quantity']);

        $this->patch(route('cart.update', ['type' => 'product', 'cartable' => $product->id]), [
            'quantity' => 1001,
        ])->assertRedirect();

        $this->assertSame(1001, app(CartService::class)->rawEntries()[0]['quantity']);
    }
}
