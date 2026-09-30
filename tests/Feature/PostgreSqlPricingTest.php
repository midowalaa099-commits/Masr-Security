<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\BulkPricingService;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

#[Group('pgsql')]
class PostgreSqlPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('Requires the disposable local PostgreSQL database.');
        }

        RefreshDatabaseState::$migrated = false;
    }

    /** Committed fixtures let two real connections exercise PostgreSQL row locks. */
    public function beginDatabaseTransaction(): void
    {
        $this->beforeApplicationDestroyed(function (): void {
            RefreshDatabaseState::$migrated = false;
            DB::purge('pgsql_competing');
            DB::disconnect('pgsql');
        });
    }

    #[TestWith(['apply', '110.06', '88.04', 'applied'])]
    #[TestWith(['undo', '100.05', '80.04', 'undone'])]
    public function test_overlapping_price_changes_are_locked_and_repeated_only_once(
        string $action,
        string $price,
        string $salePrice,
        string $status,
    ): void {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $product = Product::factory()->create(['price' => '100.05', 'sale_price' => '80.04']);
        $service = app(BulkPricingService::class);
        $batchId = $service->preview($this->pricingOptions(), $admin->id);
        $token = DB::table('bulk_price_changes')->where('id', $batchId)->value('token');
        if ($action === 'undo') {
            $service->apply($batchId, $admin->id, $token);
        }
        $before = $product->fresh()->price;
        $competing = $this->competingConnection();
        $competing->beginTransaction();
        DB::statement("SET lock_timeout = '150ms'");

        try {
            DB::setDefaultConnection('pgsql_competing');
            $service->{$action}($batchId, $admin->id, $token);
            DB::setDefaultConnection('pgsql');
            $this->assertSame($before, $product->fresh()->price);
            $blocked = null;
            try {
                $service->{$action}($batchId, $admin->id, $token);
            } catch (QueryException $exception) {
                $blocked = $exception;
            }
            $this->assertInstanceOf(QueryException::class, $blocked);
            $this->assertSame('55P03', $blocked->getCode());
            $this->assertSame($before, $product->fresh()->price);

            $competing->commit();
            $service->{$action}($batchId, $admin->id, $token);

            $this->assertSame($price, $product->fresh()->price);
            $this->assertSame($salePrice, $product->fresh()->sale_price);
            $this->assertDatabaseHas('bulk_price_changes', ['id' => $batchId, 'status' => $status]);
            $this->assertSame(1, DB::table('audit_logs')->where('action', "bulk_pricing_$status")->where('entity_id', $product->id)->count());
        } finally {
            DB::setDefaultConnection('pgsql');
            if ($competing->transactionLevel() > 0) {
                $competing->rollBack();
            }
            DB::statement("SET lock_timeout = '0'");
        }
    }

    public function test_undo_preserves_a_price_edited_on_another_connection(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $unchanged = Product::factory()->create(['price' => '100.05']);
        $edited = Product::factory()->create(['price' => '200.01']);
        $service = app(BulkPricingService::class);
        $batchId = $service->preview($this->pricingOptions(), $admin->id);
        $token = DB::table('bulk_price_changes')->where('id', $batchId)->value('token');
        $service->apply($batchId, $admin->id, $token);
        $this->competingConnection()->table('products')->where('id', $edited->id)->update([
            'price' => '230.17', 'pricing_revision' => (string) Str::uuid(),
        ]);

        $this->post(route('admin.pricing.undo', $batchId), ['token' => $token, 'confirmed' => 1])->assertRedirect();

        $this->assertSame('100.05', $unchanged->fresh()->price);
        $this->assertNull($unchanged->fresh()->sale_price);
        $this->assertSame('230.17', $edited->fresh()->price);
        $this->assertDatabaseHas('bulk_price_change_items', [
            'product_id' => $edited->id, 'status' => 'undo_conflict', 'reason' => 'changed_after_apply',
        ]);
    }

    public function test_checkout_reviews_a_price_committed_on_another_connection_before_locking(): void
    {
        $customer = User::factory()->create();
        $this->actingAs($customer);
        $product = Product::factory()->create(['price' => '1000.10', 'stock_quantity' => 10]);
        $this->post(route('cart.add'), ['type' => 'product', 'cartable' => $product->id, 'quantity' => 2])->assertRedirect();
        $this->get(route('checkout.index'))->assertOk()->assertViewHas('total', 2000.20);
        $competing = $this->competingConnection();
        $changed = false;
        DB::connection()->beforeExecuting(function (string $sql) use ($competing, $product, &$changed): void {
            if (! $changed && str_starts_with($sql, 'select') && str_contains($sql, '"products"') && str_contains($sql, 'for update')) {
                $changed = true;
                $competing->table('products')->where('id', $product->id)->update(['price' => '1200.25']);
            }
        });
        $data = ['customer_name' => 'Price Review Customer', 'phone' => '01012345678', 'payment_method' => 'cash_on_delivery'];

        $this->post(route('checkout.store'), $data)
            ->assertRedirect(route('checkout.index'))->assertSessionHas('error', __('store.cart_prices_changed'));

        $this->assertTrue($changed);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 10]);
        $this->assertDatabaseHas('cart_items', ['cartable_id' => $product->id, 'quantity' => 2]);
        $this->get(route('checkout.index'))->assertOk()->assertViewHas('total', 2400.50);
        $this->post(route('checkout.store'), $data)->assertRedirect();
        $order = Order::query()->sole();
        $this->assertSame('2400.50', $order->total);
        $this->assertSame('0.00', $order->shipping_fee);
        $this->assertSame('1200.25', $order->items()->sole()->unit_price);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 8]);
        $this->assertDatabaseCount('cart_items', 0);
    }

    private function competingConnection(): Connection
    {
        config()->set('database.connections.pgsql_competing', config('database.connections.pgsql'));

        return DB::connection('pgsql_competing');
    }

    /** @return array<string, string> */
    private function pricingOptions(): array
    {
        return [
            'scope' => 'all', 'target' => 'both', 'operation' => 'percentage',
            'direction' => 'increase', 'amount' => '10', 'rounding' => 'precision',
        ];
    }
}
