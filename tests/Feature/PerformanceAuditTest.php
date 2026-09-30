<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_editor_response_is_bounded_for_a_large_catalogue(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(500)->for($category)->create([
            'description_en' => str_repeat('Security equipment. ', 50),
            'price' => '1234.56',
        ]);
        $this->actingAs(User::factory()->admin()->create());

        $this->measurePage('package-editor', route('admin.packages.create'));
    }

    public function test_product_detail_renders_related_products_with_bounded_queries(): void
    {
        $category = Category::factory()->create();
        $products = Product::factory()->count(8)->for($category)->create();

        $this->measurePage('product-detail', route('products.show', $products->first()));
    }

    public function test_large_bulk_preview_writes_snapshots_in_bounded_batches(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(500)->for($category)->create(['price' => '1234.56', 'sale_price' => null]);
        $this->actingAs(User::factory()->admin()->create());
        DB::flushQueryLog();
        DB::enableQueryLog();
        $start = hrtime(true);

        $this->post(route('admin.pricing.store'), [
            'scope' => 'all', 'operation' => 'percentage', 'direction' => 'increase',
            'amount' => '10', 'target' => 'regular', 'rounding' => 'precision',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $elapsed = (hrtime(true) - $start) / 1e6;
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $inserts = count(array_filter($queries, fn (array $query): bool => str_starts_with($query['query'], 'insert into "bulk_price_change_items"')));
        if (getenv('MASR_BENCHMARK') === '1') {
            fwrite(STDERR, json_encode([
                'action' => 'bulk-preview', 'products' => 500, 'response_ms' => round($elapsed, 2),
                'queries' => count($queries), 'snapshot_inserts' => $inserts,
            ]).PHP_EOL);
        }
        $this->assertDatabaseCount('bulk_price_change_items', 500);
        $this->assertDatabaseHas('bulk_price_change_items', ['status' => 'ready']);
        $this->assertLessThanOrEqual(3, $inserts);
    }

    private function measurePage(string $label, string $url): void
    {
        foreach (['cold', 'warm'] as $cacheState) {
            $samples = [];
            for ($sample = 0; $sample < 5; $sample++) {
                if ($cacheState === 'cold') {
                    Cache::flush();
                }
                DB::flushQueryLog();
                DB::enableQueryLog();
                $start = hrtime(true);
                $response = $this->get($url)->assertOk();
                $elapsed = (hrtime(true) - $start) / 1e6;
                $queries = DB::getQueryLog();
                DB::disableQueryLog();
                $samples[] = $elapsed;

                $this->assertLessThan(40000, strlen($response->getContent()));
                $this->assertLessThanOrEqual($label === 'package-editor' ? 2 : 9, count($queries));
            }
            sort($samples);
            if (getenv('MASR_BENCHMARK') === '1') {
                fwrite(STDERR, json_encode([
                    'page' => $label, 'cache' => $cacheState, 'samples' => 5,
                    'median_ms' => round($samples[2], 2), 'queries' => count($queries),
                    'sql_ms' => round(array_sum(array_column($queries, 'time')), 2),
                    'html_bytes' => strlen($response->getContent()),
                    'process_peak_mib' => round(memory_get_peak_usage(true) / 1048576, 2),
                ]).PHP_EOL);
            }
        }
    }
}
