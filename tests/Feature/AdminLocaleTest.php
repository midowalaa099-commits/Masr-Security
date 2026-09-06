<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLocaleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_admin_page_remains_the_same_after_locale_switch(): void
    {
        $target = route('admin.products.index');

        $this->actingAs($this->admin())
            ->get(route('locale.switch', ['locale' => 'ar', 'back_to' => $target]))
            ->assertRedirect($target);

        $this->assertSame('ar', session('locale'));
    }

    public function test_query_parameters_are_preserved_after_locale_switch(): void
    {
        $target = route('admin.products.index').'?search=cam&status=active';

        $this->actingAs($this->admin())
            ->get(route('locale.switch', ['locale' => 'ar', 'back_to' => $target]))
            ->assertRedirect($target);

        $this->assertSame('ar', session('locale'));
    }

    public function test_search_filter_and_pagination_state_is_preserved(): void
    {
        $target = route('admin.orders.index').'?status=pending&search=ack&page=2';

        $this->actingAs($this->admin())
            ->get(route('locale.switch', ['locale' => 'en', 'back_to' => $target]))
            ->assertRedirect($target);

        $this->assertSame('en', session('locale'));
    }

    public function test_switcher_link_includes_the_current_query_string(): void
    {
        $html = $this->actingAs($this->admin())
            ->get('/admin/products?search=turret&page=3')
            ->assertOk()
            ->getContent();

        preg_match('/href="([^"]*locale\/(?:en|ar)[^"]*)"/', $html, $match);

        $this->assertNotEmpty($match);

        $query = parse_url($match[1], PHP_URL_QUERY);
        parse_str($query, $params);

        $backTo = $params['back_to'] ?? '';

        $this->assertStringContainsString('admin/products', $backTo);
        $this->assertStringContainsString('search=turret', $backTo);
        $this->assertStringContainsString('page=3', $backTo);
    }

    public function test_switching_en_to_ar_and_ar_to_en_works_for_admin_users(): void
    {
        $this->actingAs($this->admin())
            ->get(route('locale.switch', 'ar'))
            ->assertRedirect();

        $this->assertSame('ar', session('locale'));

        $this->get(route('locale.switch', 'en'))->assertRedirect();

        $this->assertSame('en', session('locale'));
    }

    public function test_relative_path_with_query_preserves_filter_state(): void
    {
        $target = route('admin.payments.index').'?status=paid';

        $this->actingAs($this->admin())
            ->get(route('locale.switch', ['locale' => 'ar', 'back_to' => $target]))
            ->assertRedirect($target);

        $this->assertSame('ar', session('locale'));
    }

    public function test_storefront_localization_still_works_after_admin_switch(): void
    {
        Category::factory()->create(['name_en' => 'Cameras', 'name_ar' => 'كاميرات']);

        $category = Category::query()->firstOrFail();

        $this->actingAs($this->admin())
            ->get(route('locale.switch', ['locale' => 'ar', 'back_to' => route('admin.categories.index')]))
            ->assertRedirect(route('admin.categories.index'));

        $this->get(route('categories.show', $category))
            ->assertOk()
            ->assertSee('كاميرات')
            ->assertDontSee('Cameras');
    }
}
