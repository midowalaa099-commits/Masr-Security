<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Package;
use App\Models\PackageItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\InventoryService;
use App\Services\SettingsService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class AuditImprovementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_lookup_requires_an_admin_and_has_bounded_pages(): void
    {
        $this->getJson(route('admin.products.options'))->assertUnauthorized();
        $this->actingAs(User::factory()->create())
            ->getJson(route('admin.products.options'))->assertForbidden();

        Product::factory()->count(55)->create();

        $first = $this->actingAs(User::factory()->admin()->create())
            ->getJson(route('admin.products.options'))
            ->assertOk()->assertJsonCount(50, 'products')->assertJsonPath('has_more', true)
            ->assertHeader('Cache-Control', 'no-store, private');
        $second = $this->getJson(route('admin.products.options', ['page' => 2]))
            ->assertOk()->assertJsonCount(5, 'products')->assertJsonPath('has_more', false);

        $this->assertEmpty(array_intersect(
            array_column($first->json('products'), 'id'), array_column($second->json('products'), 'id'),
        ));
    }

    public function test_product_lookup_searches_case_insensitively_and_treats_wildcards_literally(): void
    {
        $literal = Product::factory()->create(['name_en' => 'Camera 50%_Pro', 'name_ar' => 'كاميرا احترافية']);
        Product::factory()->create(['name_en' => 'Camera 500 Pro']);

        $this->actingAs(User::factory()->admin()->create())
            ->getJson(route('admin.products.options', ['q' => '50%_pRO']))
            ->assertOk()->assertJsonCount(1, 'products')->assertJsonPath('products.0.id', $literal->id);
        $this->getJson(route('admin.products.options', ['q' => 'احترافية']))
            ->assertOk()->assertJsonCount(1, 'products');
        $this->getJson(route('admin.products.options', ['q' => str_repeat('a', 151)]))
            ->assertUnprocessable()->assertJsonValidationErrors('q');
    }

    public function test_package_editor_keeps_a_selected_inactive_product_outside_the_first_page(): void
    {
        Product::factory()->count(55)->create(['name_en' => 'A Camera']);
        $selected = Product::factory()->create(['name_en' => 'ZZ Selected Camera', 'status' => ProductStatus::Inactive]);
        $package = Package::factory()->create();
        PackageItem::factory()->create(['package_id' => $package->id, 'product_id' => $selected->id]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.packages.edit', $package))->assertOk()
            ->assertViewHas('products', fn ($products): bool => $products->count() === 51 && $products->contains('id', $selected->id));
    }

    public function test_package_search_controls_and_lookup_names_are_localized_in_arabic(): void
    {
        $product = Product::factory()->create(['name_ar' => 'كاميرا اختبار', 'name_en' => 'Test camera']);
        $this->actingAs(User::factory()->admin()->create())->withSession(['locale' => 'ar'])
            ->get(route('admin.packages.create'))->assertOk()
            ->assertSee('ابحث عن المنتجات بالاسم أو رمز المنتج')->assertSee('السابق')->assertSee('التالي');
        $this->getJson(route('admin.products.options', ['q' => 'اختبار']))
            ->assertOk()->assertJsonPath('products.0.name', $product->name_ar);
    }

    public function test_package_calculator_returns_exact_line_totals_and_limits_input(): void
    {
        $first = Product::factory()->create(['price' => '0.10', 'sale_price' => null]);
        $second = Product::factory()->create(['price' => '0.20', 'sale_price' => null]);

        $this->actingAs(User::factory()->admin()->create())
            ->postJson(route('admin.packages.calculate'), ['items' => [
                ['product_id' => $first->id, 'quantity' => 3],
                ['product_id' => $second->id, 'quantity' => 1],
            ]])->assertOk()->assertJsonPath('total', 0.5)
            ->assertJsonPath('line_totals', ['0.30', '0.20']);
        $this->postJson(route('admin.packages.calculate'), ['items' => array_fill(0, 101, [
            'product_id' => $first->id, 'quantity' => 1,
        ])])->assertUnprocessable()->assertJsonValidationErrors('items');
    }

    public function test_package_save_validates_products_once_and_inserts_components_once(): void
    {
        $products = Product::factory()->count(20)->create();
        $this->actingAs(User::factory()->admin()->create());
        $lookups = 0;
        $inserts = 0;
        DB::listen(function (QueryExecuted $query) use (&$lookups, &$inserts): void {
            if (str_starts_with($query->sql, 'select') && str_contains($query->sql, '"products"')) {
                $lookups++;
            }
            if (str_starts_with($query->sql, 'insert into "package_items"')) {
                $inserts++;
            }
        });

        $this->post(route('admin.packages.store'), [
            'name_en' => 'Many cameras', 'name_ar' => 'كاميرات', 'slug' => 'many-cameras', 'status' => 'active',
            'items' => $products->map(fn (Product $product): array => ['product_id' => $product->id, 'quantity' => 2])->all(),
        ])->assertRedirect();

        $this->assertSame(1, $lookups);
        $this->assertSame(1, $inserts);
        $this->assertDatabaseCount('package_items', 20);
        $this->assertDatabaseHas('audit_logs', ['action' => 'package_created']);
    }

    public function test_package_save_rejects_duplicate_or_missing_components_without_writing(): void
    {
        $product = Product::factory()->create();
        $payload = ['name_en' => 'Test package', 'name_ar' => 'باقة', 'slug' => 'test-package', 'status' => 'active'];
        $this->actingAs(User::factory()->admin()->create())
            ->postJson(route('admin.packages.store'), [...$payload, 'items' => [
                ['product_id' => $product->id, 'quantity' => 1], ['product_id' => $product->id, 'quantity' => 2],
            ]])->assertUnprocessable()->assertJsonValidationErrors('items.0.product_id');
        $this->postJson(route('admin.packages.store'), [...$payload, 'items' => [
            ['product_id' => $product->id + 100, 'quantity' => 1],
        ]])->assertUnprocessable()->assertJsonValidationErrors('items.0.product_id');
        $this->assertDatabaseCount('packages', 0);
        $this->assertDatabaseCount('package_items', 0);
    }

    public function test_failed_package_audit_rolls_back_components_and_preserves_the_old_cover(): void
    {
        Storage::fake('s3');
        Storage::fake('public');
        Storage::disk('public')->put('packages/old.jpg', 'old');
        $package = Package::factory()->create(['cover_image' => 'packages/old.jpg']);
        $oldItem = PackageItem::factory()->create(['package_id' => $package->id]);
        $newProduct = Product::factory()->create();
        AuditLog::creating(function (): void {
            throw new RuntimeException('Audit storage unavailable');
        });
        $this->withoutExceptionHandling();

        try {
            $this->actingAs(User::factory()->admin()->create())
                ->put(route('admin.packages.update', $package), [
                    'name_en' => 'Changed name', 'name_ar' => $package->name_ar,
                    'slug' => $package->slug, 'status' => $package->status->value,
                    'cover_image' => UploadedFile::fake()->image('new.jpg'), 'remove_cover' => '1',
                    'items' => [['product_id' => $newProduct->id, 'quantity' => 2]],
                ]);
            $this->fail('The simulated audit failure must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Audit storage unavailable', $exception->getMessage());
        }

        $this->assertSame($package->name_en, $package->fresh()->name_en);
        $this->assertSame('packages/old.jpg', $package->fresh()->cover_image);
        $this->assertDatabaseHas('package_items', ['id' => $oldItem->id]);
        $this->assertDatabaseCount('package_items', 1);
        $this->assertDatabaseCount('audit_logs', 0);
        Storage::disk('public')->assertExists('packages/old.jpg');
        $this->assertSame([], Storage::disk('s3')->allFiles());
    }

    public function test_settings_bulk_write_is_one_query_and_invalidates_cached_values(): void
    {
        $settings = app(SettingsService::class);
        $settings->set('company_name', 'Old');
        $this->assertSame('Old', $settings->get('company_name'));
        $writes = 0;
        DB::listen(function (QueryExecuted $query) use (&$writes): void {
            if (str_starts_with($query->sql, 'insert into "settings"')) {
                $writes++;
            }
        });

        $settings->setMany(['company_name' => 'New', 'phone' => '01000000000', 'optional' => null]);

        $this->assertSame(1, $writes);
        $this->assertSame('New', $settings->get('company_name'));
        $this->assertNull($settings->get('optional', 'fallback'));
        $this->assertSame('01000000000', $settings->get('phone'));
    }

    public function test_checkout_preserves_exact_totals_and_aggregates_shared_package_components(): void
    {
        $product = Product::factory()->create(['price' => '0.10', 'sale_price' => null, 'stock_quantity' => 20]);
        $package = Package::factory()->create(['use_component_pricing' => true, 'discount_amount' => '0.00']);
        PackageItem::factory()->create(['package_id' => $package->id, 'product_id' => $product->id, 'quantity' => 5]);
        $this->assertSame('0.50', $package->effectivePricing());
        $this->post(route('cart.add'), ['type' => 'package', 'cartable' => $package->id, 'quantity' => 2])->assertRedirect();
        $this->post(route('cart.add'), ['type' => 'product', 'cartable' => $product->id, 'quantity' => 3])->assertRedirect();
        $this->assertSame('1.30', app(CartService::class)->subtotal());

        $this->post(route('checkout.store'), [
            'customer_name' => 'Exact Customer', 'phone' => '01000000000',
            'email' => 'exact@example.test', 'address_line' => 'Test address', 'payment_method' => 'cash_on_delivery',
        ])->assertRedirect();

        $order = Order::query()->firstOrFail();
        $this->assertSame('1.30', $order->total);
        $this->assertSame('0.00', $order->shipping_fee);
        $this->assertSame(7, $product->fresh()->stock_quantity);
        $this->assertSame(10, $order->items->first()->inventory_snapshot[$product->id]);
        app(InventoryService::class)->restoreForOrder($order);
        $this->assertSame(20, $product->fresh()->stock_quantity);
    }

    public function test_inventory_handles_legacy_product_and_package_lines_without_snapshots(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 20]);
        $package = Package::factory()->create();
        PackageItem::factory()->create(['package_id' => $package->id, 'product_id' => $product->id, 'quantity' => 2]);
        $order = Order::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id, 'orderable_type' => Product::class, 'orderable_id' => $product->id,
            'inventory_snapshot' => null, 'quantity' => 3,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id, 'orderable_type' => Package::class, 'orderable_id' => $package->id,
            'inventory_snapshot' => null, 'quantity' => 2,
        ]);

        DB::transaction(fn () => app(InventoryService::class)->decrementForOrder($order));
        $this->assertSame(13, $product->fresh()->stock_quantity);
        app(InventoryService::class)->restoreForOrder($order);
        $this->assertSame(20, $product->fresh()->stock_quantity);
    }
}
