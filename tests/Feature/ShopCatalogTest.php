<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Services\CatalogCategories;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ShopCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_categories_are_cached_and_invalidated_after_admin_edits(): void
    {
        $category = Category::factory()->create();
        $catalog = app(CatalogCategories::class);
        DB::enableQueryLog();
        try {
            DB::flushQueryLog();
            $catalog->active();
            $this->assertCount(1, DB::getQueryLog());
            $this->assertSame(
                (array) DB::table('categories')->where('id', $category->id)->first(),
                Cache::get('storefront.categories.v1')[0],
            );
            DB::flushQueryLog();
            $this->assertSame([$category->id], $catalog->active()->modelKeys());
            $this->assertCount(0, DB::getQueryLog());

            $category->update(['name_en' => 'Updated category']);
            $this->assertSame('Updated category', $catalog->active()->first()->name_en);
            $second = Category::factory()->create();
            $this->assertCount(2, $catalog->active());
            $second->delete();
            $this->assertSame([$category->id], $catalog->active()->modelKeys());
        } finally {
            DB::disableQueryLog();
        }
    }

    public function test_section_categories_follow_product_reassignment_without_stale_cache(): void
    {
        $old = Category::factory()->create();
        $new = Category::factory()->create();
        $product = Product::factory()->for($old)->create(['brand' => 'HiLook']);
        $this->get(route('shop', ['section' => 'hilook']))->assertViewHas('categories', fn ($categories) => $categories->modelKeys() === [$old->id]);

        $product->update(['category_id' => $new->id]);

        $this->get(route('shop', ['section' => 'hilook']))->assertViewHas('categories', fn ($categories) => $categories->modelKeys() === [$new->id]);
    }

    public function test_categories_render_once_and_selected_ancestors_are_expanded(): void
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->childOf($parent)->create();
        $grandchild = Category::factory()->childOf($child)->create();
        $inactive = Category::factory()->inactive()->childOf($parent)->create();

        $response = $this->get(route('shop', ['category' => $grandchild->id]));

        $response->assertOk();
        $document = new DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new DOMXPath($document);
        foreach ([$parent, $child, $grandchild] as $category) {
            $this->assertSame(1, $xpath->query('//input[@type="radio" and @name="category" and @value="'.$category->id.'"]')->length);
        }
        $this->assertSame(0, $xpath->query('//input[@type="radio" and @name="category" and @value="'.$inactive->id.'"]')->length);
        $this->assertSame(2, $xpath->query('//details[@open]/summary')->length);
    }

    public function test_parent_filter_includes_descendants_at_every_depth(): void
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->childOf($parent)->create();
        $grandchild = Category::factory()->childOf($child)->create();
        $direct = Product::factory()->for($parent)->create();
        $nested = Product::factory()->for($grandchild)->create();
        Product::factory()->create();

        $response = $this->get(route('shop', ['category' => $parent->id]));

        $response->assertOk();
        $this->assertSame([$direct->id, $nested->id], $response->viewData('products')->getCollection()->sortBy('id')->modelKeys());
    }

    /** @return array<string, array{string, string}> */
    public static function brands(): array
    {
        return [
            'Hikvision' => [' HIKVISION ', ' hikVISION '],
            'HiLook' => [' HiLook ', ' HILOOK '],
            'EZVIZ' => [' ezviz ', ' EZVIZ '],
        ];
    }

    #[DataProvider('brands')]
    public function test_brand_sections_normalize_case_and_spaces_without_changing_data(string $brand, string $section): void
    {
        $product = Product::factory()->create(['brand' => $brand]);
        Product::factory()->create(['brand' => 'Other']);
        Product::factory()->create(['brand' => null]);
        Product::factory()->create(['brand' => $brand, 'status' => ProductStatus::Inactive]);

        $response = $this->get(route('shop', ['section' => $section]));

        $response->assertOk();
        $this->assertSame([$product->id], $response->viewData('products')->modelKeys());
        $this->assertSame($brand, $product->fresh()->brand);
    }

    public function test_audio_section_includes_audio_descendants_regardless_of_brand(): void
    {
        $audio = Category::factory()->create(['slug' => 'audio-systems']);
        $speakers = Category::factory()->childOf($audio)->create();
        $product = Product::factory()->for($speakers)->create(['brand' => 'Other']);
        Product::factory()->create(['brand' => 'Hikvision']);

        $response = $this->get(route('shop', ['section' => 'audio-systems']));

        $response->assertOk();
        $this->assertSame([$product->id], $response->viewData('products')->modelKeys());
    }

    public function test_empty_sections_remain_available_in_both_locales(): void
    {
        $this->get(route('shop', ['section' => 'audio-systems']))
            ->assertSeeInOrder(['All Products', 'Hikvision', 'HiLook', 'EZVIZ', 'Audio Systems'])
            ->assertSee(__('store.no_products'));

        $this->withSession(['locale' => 'ar'])->get(route('shop'))
            ->assertSee('جميع المنتجات')->assertSee('الأنظمة الصوتية');
    }

    /** @return array<string, array{mixed}> */
    public static function invalidSections(): array
    {
        return ['unknown' => ['unknown'], 'array' => [['hikvision']], 'injection' => ["hikvision' OR 1=1 --"]];
    }

    #[DataProvider('invalidSections')]
    public function test_invalid_sections_fall_back_to_all_products(mixed $section): void
    {
        $product = Product::factory()->create();

        $response = $this->get(route('shop', ['section' => $section]));

        $response->assertOk()->assertViewHas('section', 'all');
        $this->assertSame([$product->id], $response->viewData('products')->modelKeys());
    }

    public function test_section_combines_with_category_search_effective_price_and_sort(): void
    {
        $category = Category::factory()->create();
        $cheap = Product::factory()->for($category)->create(['brand' => 'HiLook', 'name_en' => 'Camera cheap', 'price' => 500, 'sale_price' => 150]);
        $expensive = Product::factory()->for($category)->create(['brand' => 'HiLook', 'name_en' => 'Camera expensive', 'price' => 200]);
        Product::factory()->for($category)->create(['brand' => 'HiLook', 'name_en' => 'Camera excluded', 'price' => 50]);
        Product::factory()->for($category)->create(['brand' => 'EZVIZ', 'name_en' => 'Camera other brand', 'price' => 180]);
        Product::factory()->for($category)->create(['brand' => 'HiLook', 'name_en' => 'Recorder', 'price' => 180]);
        Product::factory()->create(['brand' => 'HiLook', 'name_en' => 'Camera different category', 'price' => 180]);

        $response = $this->get(route('shop', ['section' => 'hilook', 'category' => $category->id, 'q' => 'Camera', 'min_price' => 100, 'max_price' => 250, 'sort' => 'price_asc']));

        $response->assertOk()->assertSee('name="section" value="hilook"', false);
        $this->assertSame([$cheap->id, $expensive->id], $response->viewData('products')->modelKeys());
    }

    public function test_pagination_preserves_section_and_filters(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(13)->for($category)->create(['brand' => 'EZVIZ', 'name_en' => 'Camera', 'price' => 200]);

        $response = $this->get(route('shop', ['section' => 'ezviz', 'category' => $category->id, 'q' => 'Camera', 'min_price' => 100, 'sort' => 'price_asc']));

        $response->assertOk();
        $products = $response->viewData('products');
        $this->assertSame(13, $products->total());
        parse_str(parse_url($products->nextPageUrl(), PHP_URL_QUERY), $query);
        $this->assertSame(['section' => 'ezviz', 'category' => (string) $category->id, 'q' => 'Camera', 'min_price' => '100', 'sort' => 'price_asc', 'page' => '2'], $query);
    }

    public function test_price_filter_uses_displayed_price_for_legacy_invalid_discounts(): void
    {
        $zeroSale = Product::factory()->create(['price' => 200, 'sale_price' => 0]);
        $higherSale = Product::factory()->create(['price' => 150, 'sale_price' => 300]);

        $response = $this->get(route('shop', ['min_price' => 100, 'max_price' => 250, 'sort' => 'price_asc']));

        $response->assertOk();
        $this->assertSame([$higherSale->id, $zeroSale->id], $response->viewData('products')->modelKeys());
    }
}
