<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Package;
use App\Models\PackageItem;
use App\Models\Product;
use App\Models\QuoteRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_home_shop_and_informational_pages(): void
    {
        Category::factory()->count(2)->create();
        Product::factory()->count(3)->featured()->create();

        $this->get('/')->assertOk();
        $this->get(route('shop'))->assertOk();
        $this->get(route('about'))->assertOk();
        $this->get(route('contact'))->assertOk();
        $this->get(route('quote.create'))->assertOk();
        $this->get('/login')->assertOk();
    }

    public function test_guest_header_includes_login_and_registration_links_in_both_locales(): void
    {
        $this->get(route('home'))
            ->assertSee(route('login'))
            ->assertSee(route('register'))
            ->assertSee(__('store.login'))
            ->assertSee(__('store.register'))
            ->assertSee('Skip to content')
            ->assertSee('aria-label="Navigation"', false)
            ->assertSee('All rights reserved.')
            ->assertSee('100%')
            ->assertSee('24 months')
            ->assertSee('Cairo');

        $this->withSession(['locale' => 'ar'])
            ->get(route('home'))
            ->assertSee('تسجيل الدخول')
            ->assertSee('إنشاء حساب')
            ->assertSee('انتقل إلى المحتوى')
            ->assertSee('aria-label="التنقل"', false)
            ->assertSee('جميع الحقوق محفوظة.')
            ->assertSee('١٠٠٪')
            ->assertSee('٢٤ شهرًا')
            ->assertSee('القاهرة');
    }

    public function test_category_shop_and_detail_pages_render(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->get(route('categories.show', $category))->assertOk()->assertSee($product->name_en);
        $this->get(route('shop', ['category' => $category->id]))->assertOk();
        $this->get(route('products.show', $product))->assertOk()->assertSee($product->name_en);
    }

    public function test_package_listing_and_detail_pages_render(): void
    {
        $componentA = Product::factory()->component()->create(['price' => 500, 'stock_quantity' => 10]);
        $componentB = Product::factory()->component()->create(['price' => 300, 'stock_quantity' => 10]);

        $package = Package::factory()->create();
        $package->items()->saveMany([
            new PackageItem(['product_id' => $componentA->id, 'quantity' => 1]),
            new PackageItem(['product_id' => $componentB->id, 'quantity' => 1]),
        ]);

        $this->get(route('packages.index'))->assertOk();
        $this->get(route('packages.show', $package))->assertOk();
    }

    public function test_customer_can_submit_a_quote_request(): void
    {
        $this->post(route('quote.store'), [
            'name' => 'Mohamed',
            'phone' => '01000000000',
            'email' => 'mohamed@example.com',
            'company' => 'Acme',
            'message' => 'Need a quote for 5 cameras.',
            'requested_products' => ['Bullet Camera', 'NVR'],
        ])->assertRedirect(route('quote.create'));

        $this->assertDatabaseHas('quote_requests', [
            'name' => 'Mohamed',
            'phone' => '01000000000',
            'email' => 'mohamed@example.com',
        ]);
    }

    public function test_quote_request_validates_phone(): void
    {
        $this->from(route('quote.create'))
            ->post(route('quote.store'), [
                'name' => 'Mohamed',
                'phone' => 'not-a-phone',
            ])
            ->assertSessionHasErrors('phone');

        $this->assertCount(0, QuoteRequest::all());
    }

    public function test_locale_switch_persists_locale(): void
    {
        $this->post(route('locale.switch', 'ar'))
            ->assertRedirect()
            ->assertSessionHas('locale', 'ar');

        $this->get(route('home'))->assertOk();
    }
}
