<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductBrandTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_a_brand_and_it_becomes_available_on_product_forms(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.brands.index'))
            ->assertSee('Hikvision')
            ->assertSee('HiLook')
            ->assertSee('EZVIZ')
            ->assertDontSee('Dahua');

        $this->actingAs($admin)
            ->post(route('admin.brands.store'), ['name' => '  Uniview  '])
            ->assertRedirect(route('admin.brands.index'))
            ->assertSessionHas('success');

        $brand = ProductBrand::query()->where('name', 'Uniview')->firstOrFail();

        $this->assertDatabaseHas('product_brands', ['id' => $brand->id, 'name' => 'Uniview']);
        $this->actingAs($admin)
            ->get(route('admin.products.create'))
            ->assertSee('value="Uniview"', false)
            ->assertDontSee('value="Dahua"', false);
    }

    public function test_admin_cannot_add_a_duplicate_brand(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.brands.index'))
            ->post(route('admin.brands.store'), ['name' => 'Hikvision'])
            ->assertRedirect(route('admin.brands.index'))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('product_brands', 3);
    }

    public function test_admin_cannot_remove_a_brand_assigned_to_a_product(): void
    {
        $admin = User::factory()->admin()->create();
        $brand = ProductBrand::query()->where('name', 'HiLook')->firstOrFail();
        Product::factory()->create(['brand' => $brand->name]);

        $this->actingAs($admin)
            ->from(route('admin.brands.index'))
            ->delete(route('admin.brands.destroy', $brand))
            ->assertRedirect(route('admin.brands.index'))
            ->assertSessionHas('error', __('admin.brand_in_use', ['brand' => $brand->name]));

        $this->assertDatabaseHas('product_brands', ['id' => $brand->id, 'name' => 'HiLook']);
    }

    public function test_admin_can_remove_a_brand_not_assigned_to_any_product(): void
    {
        $admin = User::factory()->admin()->create();
        $brand = ProductBrand::query()->where('name', 'EZVIZ')->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('admin.brands.destroy', $brand))
            ->assertRedirect(route('admin.brands.index'))
            ->assertSessionHas('success', __('admin.brand_deleted'));

        $this->assertDatabaseMissing('product_brands', ['id' => $brand->id]);
        $this->actingAs($admin)
            ->get(route('admin.products.create'))
            ->assertDontSee('value="EZVIZ"', false);
    }

    public function test_admin_can_assign_a_managed_brand_to_an_existing_product(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['brand' => 'HiLook']);

        $this->actingAs($admin)
            ->put(route('admin.products.update', $product), [
                'sku' => $product->sku,
                'name_ar' => $product->name_ar,
                'name_en' => $product->name_en,
                'slug' => $product->slug,
                'brand' => 'EZVIZ',
                'price' => $product->price,
                'stock_quantity' => $product->stock_quantity,
                'low_stock_threshold' => $product->low_stock_threshold,
                'status' => $product->status->value,
                'type' => $product->type->value,
            ])
            ->assertRedirect(route('admin.products.edit', $product));

        $this->assertSame('EZVIZ', $product->fresh()->brand);
    }

    public function test_non_admin_cannot_manage_brands(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('admin.brands.index'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->post(route('admin.brands.store'), ['name' => 'Uniview'])
            ->assertForbidden();
    }

    public function test_product_cannot_be_created_with_a_brand_outside_the_managed_catalog(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'sku' => 'INVALID-BRAND-01',
                'name_en' => 'Invalid Brand Camera',
                'name_ar' => 'كاميرا بعلامة غير مسجلة',
                'brand' => 'Dahua',
                'price' => 1200,
                'low_stock_threshold' => 3,
                'status' => 'draft',
                'type' => 'simple',
            ])
            ->assertRedirect(route('admin.products.create'))
            ->assertSessionHasErrors('brand');

        $this->assertDatabaseMissing('products', ['sku' => 'INVALID-BRAND-01']);
    }

    public function test_editing_a_legacy_product_preserves_its_unmanaged_brand_option(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['brand' => 'Legacy Brand']);

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $product))
            ->assertSee('value="Legacy Brand"', false)
            ->assertSee(__('admin.brand_existing'));
    }
}
