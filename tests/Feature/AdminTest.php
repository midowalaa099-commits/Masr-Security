<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function customer(): User
    {
        return User::factory()->create();
    }

    public function test_admin_area_rejects_customers(): void
    {
        $this->actingAs($this->customer())
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_area_requires_authentication(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_admin_can_view_the_dashboard_and_modules(): void
    {
        Category::factory()->count(2)->create();

        $this->actingAs($this->admin())
            ->get('/admin')
            ->assertOk();

        $this->actingAs($this->admin())->get(route('admin.categories.index'))->assertOk();
        $this->actingAs($this->admin())->get(route('admin.products.index'))->assertOk();
        $this->actingAs($this->admin())->get(route('admin.products.create'))->assertOk();
        $this->actingAs($this->admin())->get(route('admin.packages.index'))->assertOk();
        $this->actingAs($this->admin())->get(route('admin.orders.index'))->assertOk();
        $this->actingAs($this->admin())->get(route('admin.settings.edit'))->assertOk();
        $this->actingAs($this->admin())->get(route('admin.audit-logs.index'))->assertOk();
    }

    public function test_admin_can_create_a_product(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.products.store'), [
                'category_id' => $category->id,
                'sku' => 'HIK-TEST-001',
                'name_ar' => 'كاميرا اختبار',
                'name_en' => 'Test Camera',
                'slug' => 'test-camera',
                'description_ar' => 'aaa',
                'description_en' => 'bbb',
                'brand' => 'Hikvision',
                'model_number' => 'DS-1',
                'price' => 1200,
                'stock_quantity' => 15,
                'low_stock_threshold' => 3,
                'status' => ProductStatus::Active->value,
                'type' => ProductType::Simple->value,
            ])
            ->assertRedirect(route('admin.products.edit', Product::where('sku', 'HIK-TEST-001')->firstOrFail()));

        $this->assertDatabaseHas('products', ['sku' => 'HIK-TEST-001', 'slug' => 'test-camera']);
    }

    public function test_admin_cancelling_an_open_order_restores_stock(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 5]);

        $order = Order::factory()->create(['status' => OrderStatus::Pending]);

        $order->items()->create([
            'orderable_type' => Product::class,
            'orderable_id' => $product->id,
            'name_snapshot' => $product->name_en,
            'sku_snapshot' => $product->sku,
            'inventory_snapshot' => [$product->id => 2],
            'unit_price' => 500,
            'quantity' => 2,
            'line_total' => 1000,
        ]);

        $order->payments()->save(Payment::factory()->successful()->for($order)->create());

        $this->actingAs($this->admin())
            ->post(route('admin.orders.status', $order), ['status' => OrderStatus::Cancelled->value])
            ->assertRedirect();

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(7, (int) $product->fresh()->stock_quantity);
    }

    public function test_admin_package_calculate_returns_the_components_total(): void
    {
        $componentA = Product::factory()->component()->create(['price' => 500]);
        $componentB = Product::factory()->component()->create(['price' => 300]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.packages.calculate'), [
                'items' => [
                    ['product_id' => $componentA->id, 'quantity' => 2],
                    ['product_id' => $componentB->id, 'quantity' => 1],
                ],
            ])
            ->assertOk()
            ->assertJson(['total' => 1300]);
    }

    public function test_admin_cannot_mark_a_delivered_order_as_cancelled(): void
    {
        $order = Order::factory()->delivered()->create();
        $order->payments()->save(Payment::factory()->successful()->for($order)->create());

        $this->actingAs($this->admin())
            ->post(route('admin.orders.status', $order), ['status' => OrderStatus::Cancelled->value])
            ->assertRedirect();

        $this->assertSame(OrderStatus::Delivered, $order->fresh()->status);
    }
}
