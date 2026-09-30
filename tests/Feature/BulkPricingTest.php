<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\PackageItem;
use App\Models\Product;
use App\Models\User;
use App\Services\BulkPriceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class BulkPricingTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['100.05', 'percentage', 'increase', '10', 'precision', '110.06'])]
    #[TestWith(['100.00', 'percentage', 'decrease', '5', 'precision', '95.00'])]
    #[TestWith(['100.00', 'fixed', 'increase', '2.5', '5', '105.00'])]
    #[TestWith(['100.00', 'fixed', 'decrease', '6', '10', '90.00'])]
    public function test_decimal_adjustments_and_rounding(string $price, string $operation, string $direction, string $amount, string $rounding, string $expected): void
    {
        $this->assertSame($expected, app(BulkPriceCalculator::class)->calculate($price, compact('operation', 'direction', 'amount', 'rounding')));
    }

    public function test_preview_apply_duplicate_submission_and_undo(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['price' => 1000, 'sale_price' => 800]);
        $this->actingAs($admin);
        $batch = $this->preview(['target' => 'both']);

        $this->assertSame('1000.00', $product->fresh()->price);
        $this->get(route('admin.pricing.show', $batch->id))->assertSee('1000.00')->assertSee('1100.00')->assertDontSee('pricing.title');
        $this->applyBatch($batch)->assertRedirect(route('admin.pricing.show', $batch->id));
        $this->applyBatch($batch)->assertRedirect();
        $this->assertSame('1100.00', $product->fresh()->price);
        $this->assertSame('880.00', $product->fresh()->sale_price);
        $this->assertDatabaseHas('audit_logs', ['action' => 'bulk_pricing_applied', 'user_id' => $admin->id, 'entity_id' => $product->id]);

        $this->post(route('admin.pricing.undo', $batch->id), ['confirmed' => 1, 'token' => $batch->token])->assertRedirect();
        $this->post(route('admin.pricing.undo', $batch->id), ['confirmed' => 1, 'token' => $batch->token])->assertRedirect();
        $this->assertSame('1000.00', $product->fresh()->price);
        $this->assertSame('800.00', $product->fresh()->sale_price);
    }

    public function test_apply_accepts_an_unchanged_snapshot_with_reordered_json_keys(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['price' => 100]);
        $this->actingAs($admin);
        $batch = $this->preview();
        $item = DB::table('bulk_price_change_items')->where('bulk_price_change_id', $batch->id)->first();
        $before = json_decode($item->before, true, flags: JSON_THROW_ON_ERROR);
        DB::table('bulk_price_change_items')->where('id', $item->id)->update([
            'before' => json_encode(array_reverse($before, true), JSON_THROW_ON_ERROR),
        ]);

        $this->applyBatch($batch)->assertRedirect(route('admin.pricing.show', $batch->id));

        $this->assertSame('110.00', $product->fresh()->price);
        $this->assertDatabaseHas('bulk_price_changes', ['id' => $batch->id, 'status' => 'applied']);
    }

    public function test_stale_preview_rolls_back_every_product_and_requires_refresh(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $first = Product::factory()->create(['price' => 100]);
        $second = Product::factory()->create(['price' => 200]);
        $batch = $this->preview();
        $second->update(['price' => 250]);

        $this->from(route('admin.pricing.show', $batch->id))->applyBatch($batch)->assertSessionHasErrors('pricing');

        $this->assertSame('100.00', $first->fresh()->price);
        $this->assertSame('250.00', $second->fresh()->price);
        $this->assertDatabaseHas('bulk_price_changes', ['id' => $batch->id, 'status' => 'preview']);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'bulk_pricing_applied']);
    }

    public function test_undo_preserves_later_edits_even_when_price_returns_to_same_value(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $product = Product::factory()->create(['price' => 100]);
        $batch = $this->preview();
        $this->applyBatch($batch)->assertRedirect();
        $product->refresh()->update(['price' => 120]);
        $product->update(['price' => 110]);

        $this->post(route('admin.pricing.undo', $batch->id), ['confirmed' => 1, 'token' => $batch->token])->assertRedirect();

        $this->assertSame('110.00', $product->fresh()->price);
        $this->assertDatabaseHas('bulk_price_change_items', ['product_id' => $product->id, 'status' => 'undo_conflict']);
    }

    public function test_invalid_prices_and_absent_sale_prices_are_skipped(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $missing = Product::factory()->create(['price' => 100]);
        $discounted = Product::factory()->create(['price' => 100, 'sale_price' => 90]);
        $batch = $this->preview(['target' => 'sale', 'amount' => '20']);

        $this->applyBatch($batch)->assertRedirect();

        $this->assertNull($missing->fresh()->sale_price);
        $this->assertSame('90.00', $discounted->fresh()->sale_price);
        $this->assertDatabaseHas('bulk_price_change_items', ['product_id' => $missing->id, 'reason' => 'no_sale_price']);
        $this->assertDatabaseHas('bulk_price_change_items', ['product_id' => $discounted->id, 'reason' => 'sale_not_below_regular']);
        $negative = $this->preview(['direction' => 'decrease', 'operation' => 'fixed', 'amount' => '200']);
        $this->applyBatch($negative)->assertRedirect();
        $this->assertSame('100.00', $missing->fresh()->price);
    }

    #[TestWith(['brand'])]
    #[TestWith(['category'])]
    #[TestWith(['selected'])]
    public function test_scopes_only_adjust_matching_products(string $scope): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $included = Product::factory()->create(['price' => 100, 'brand' => ' HiLook ']);
        $excluded = Product::factory()->create(['price' => 100, 'brand' => 'EZVIZ']);
        $batch = $this->preview(['scope' => $scope, 'brand' => $included->brand, 'category_id' => $included->category_id, 'product_ids' => [$included->id]]);

        $this->applyBatch($batch)->assertRedirect();

        $this->assertSame('110.00', $included->fresh()->price);
        $this->assertSame('100.00', $excluded->fresh()->price);
    }

    public function test_packages_keep_their_existing_pricing_policy(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $product = Product::factory()->create(['price' => 100]);
        $fixed = Package::factory()->create(['base_price' => 500, 'use_component_pricing' => false]);
        $dynamic = Package::factory()->create(['use_component_pricing' => true, 'discount_amount' => 10]);
        foreach ([$fixed, $dynamic] as $package) {
            PackageItem::factory()->for($package)->for($product)->create(['quantity' => 2]);
        }
        $batch = $this->preview();

        $this->applyBatch($batch)->assertRedirect();

        $this->assertSame('500.00', $fixed->fresh()->displayPrice());
        $this->assertSame('210.00', $dynamic->fresh()->displayPrice());
    }

    public function test_access_confirmation_expiry_and_batch_ownership(): void
    {
        $this->get(route('admin.pricing.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->post(route('admin.pricing.store'), $this->pricingOptions())->assertForbidden();
        $this->assertDatabaseCount('bulk_price_changes', 0);
        $this->actingAs(User::factory()->admin()->create());
        $product = Product::factory()->create(['price' => 100]);
        $batch = $this->preview();
        $this->post(route('admin.pricing.apply', $batch->id), ['token' => $batch->token])->assertSessionHasErrors('confirmed');
        $this->travel(61)->minutes();
        $this->applyBatch($batch)->assertStatus(409);
        $this->travelBack();
        $this->actingAs(User::factory()->admin()->create());
        $this->get(route('admin.pricing.show', $batch->id))->assertForbidden();
        $this->applyBatch($batch)->assertForbidden();
        $this->assertSame('100.00', $product->fresh()->price);
    }

    public function test_pricing_pages_render_in_arabic_and_reject_invalid_options(): void
    {
        $this->actingAs(User::factory()->admin()->create())->withSession(['locale' => 'ar']);
        $this->get(route('admin.pricing.index'))->assertSee('تعديل الأسعار بالجملة')->assertDontSee('pricing.title');
        $this->post(route('admin.pricing.store'), $this->pricingOptions(['amount' => '-10', 'scope' => 'untrusted', 'rounding' => '3']))
            ->assertSessionHasErrors(['amount', 'scope', 'rounding']);
        $this->assertDatabaseCount('bulk_price_changes', 0);
    }

    private function pricingOptions(array $overrides = []): array
    {
        return array_replace(['scope' => 'all', 'operation' => 'percentage', 'direction' => 'increase', 'amount' => '10', 'target' => 'regular', 'rounding' => 'precision'], $overrides);
    }

    private function preview(array $overrides = []): \stdClass
    {
        $this->post(route('admin.pricing.store'), $this->pricingOptions($overrides))->assertSessionHasNoErrors()->assertRedirect();

        return DB::table('bulk_price_changes')->orderByDesc('id')->first();
    }

    private function applyBatch(\stdClass $batch): TestResponse
    {
        return $this->post(route('admin.pricing.apply', $batch->id), ['confirmed' => 1, 'token' => $batch->token]);
    }
}
