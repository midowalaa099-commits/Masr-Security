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
use App\Services\InventoryService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_admin_can_keep_a_negative_internal_stock_count_when_editing(): void
    {
        $product = Product::factory()->create(['stock_quantity' => -3]);

        $this->actingAs($this->admin())
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('name="stock_quantity"', false)
            ->assertDontSee('min="0" name="stock_quantity"', false);

        $this->put(route('admin.products.update', $product), [
            'sku' => $product->sku,
            'name_ar' => $product->name_ar,
            'name_en' => $product->name_en,
            'slug' => $product->slug,
            'price' => $product->price,
            'stock_quantity' => -3,
            'low_stock_threshold' => $product->low_stock_threshold,
            'status' => $product->status->value,
            'type' => $product->type->value,
        ])->assertRedirect(route('admin.products.edit', $product));

        $this->assertSame(-3, (int) $product->fresh()->stock_quantity);
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

    public function test_order_status_audit_is_rolled_back_when_the_status_update_fails(): void
    {
        $order = Order::factory()->paid()->create();
        Payment::factory()->successful()->for($order)->create();

        $this->mock(InventoryService::class)
            ->shouldReceive('restoreForOrder')
            ->once()
            ->andThrow(new \RuntimeException('Inventory restoration failed.'));

        $this->actingAs($this->admin())
            ->post(route('admin.orders.status', $order), ['status' => OrderStatus::Cancelled->value])
            ->assertServerError();

        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'order_status_changed',
            'entity_type' => Order::class,
            'entity_id' => $order->id,
        ]);
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

    public function test_admin_package_calculate_fetches_all_products_in_one_query(): void
    {
        $componentA = Product::factory()->component()->create(['price' => 500]);
        $componentB = Product::factory()->component()->create(['price' => 300]);
        $productSelects = 0;

        DB::listen(function (QueryExecuted $query) use (&$productSelects): void {
            if (str_contains(strtolower($query->sql), 'from "products"')) {
                $productSelects++;
            }
        });

        $this->actingAs($this->admin())
            ->postJson(route('admin.packages.calculate'), [
                'items' => [
                    ['product_id' => $componentA->id, 'quantity' => 2],
                    ['product_id' => $componentB->id, 'quantity' => 1],
                ],
            ])
            ->assertOk()
            ->assertJson(['total' => 1300]);

        $this->assertSame(1, $productSelects);
    }

    public function test_admin_package_calculate_rejects_unknown_product_ids(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('admin.packages.calculate'), [
                'items' => [
                    ['product_id' => 999999, 'quantity' => 1],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.product_id');
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
