<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_switching_from_english_to_arabic_persists_locale_and_redirects_back(): void
    {
        $response = $this->get(route('locale.switch', ['locale' => 'ar', 'back_to' => route('shop')]));

        $response->assertRedirect(route('shop'));
        $this->assertSame('ar', session('locale'));
    }

    public function test_switching_from_arabic_to_english_persists_locale(): void
    {
        $this->withSession(['locale' => 'ar'])
            ->get(route('locale.switch', 'en'))
            ->assertRedirect();

        $this->assertSame('en', session('locale'));
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $this->get('/locale/fr')->assertNotFound();

        $this->assertNull(session('locale'));
    }

    public function test_locale_persists_in_session_across_requests(): void
    {
        $this->withSession(['locale' => 'ar'])->get('/')->assertOk();

        $this->assertSame('ar', session('locale'));
    }

    public function test_switching_to_arabic_sets_rtl_dir_and_html_lang(): void
    {
        $this->withSession(['locale' => 'ar'])
            ->get('/')
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl"', false);
    }

    public function test_switching_to_english_sets_ltr_dir_and_html_lang(): void
    {
        $this->withSession(['locale' => 'en'])
            ->get('/')
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr"', false);
    }

    public function test_translated_product_name_changes_with_locale(): void
    {
        $category = Category::factory()->create([
            'name_en' => 'Cameras',
            'name_ar' => 'كاميرات',
        ]);

        $response = $this->withSession(['locale' => 'en'])
            ->get(route('categories.show', $category));

        $response->assertOk()->assertSee('Cameras')->assertDontSee('كاميرات');

        $response = $this->withSession(['locale' => 'ar'])
            ->get(route('categories.show', $category));

        $response->assertOk()->assertSee('كاميرات')->assertDontSee('Cameras');
    }

    public function test_switcher_uses_get_route_and_does_not_require_locale_field(): void
    {
        $this->get(route('locale.switch', 'ar'))->assertRedirect();

        $this->assertSame('ar', session('locale'));
    }

    public function test_english_header_groups_language_switch_with_actions_and_keeps_shop_filters(): void
    {
        $this->withSession(['locale' => 'en'])
            ->get('/shop?q=camera')
            ->assertSeeInOrder([
                '<header ',
                'data-header-actions',
                e(route('locale.switch', ['locale' => 'ar', 'back_to' => '/shop?q=camera'])),
                'data-language-switch',
                'العربية',
                '</header>',
            ], false);
    }

    public function test_arabic_header_offers_english_in_the_same_action_group(): void
    {
        $this->withSession(['locale' => 'ar'])
            ->get('/shop?q=camera')
            ->assertSeeInOrder([
                '<header ',
                'data-header-actions',
                e(route('locale.switch', ['locale' => 'en', 'back_to' => '/shop?q=camera'])),
                'data-language-switch',
                'English',
                '</header>',
            ], false);
    }
}
