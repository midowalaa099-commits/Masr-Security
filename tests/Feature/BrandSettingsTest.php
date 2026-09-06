<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class BrandSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_admin_can_upload_logo_hero_and_gallery_images(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->put(route('admin.settings.update'), [
                'company_name' => 'MASR Security',
                'shipping_fee' => '60',
                'facebook' => 'https://www.facebook.com/profile.php?id=61586652051946',
                'site_logo' => UploadedFile::fake()->image('logo.png', 200, 80),
                'hero_image' => UploadedFile::fake()->image('hero.png', 1600, 900),
                'gallery_images' => [
                    UploadedFile::fake()->image('gallery-1.png', 1200, 900),
                    UploadedFile::fake()->image('gallery-2.png', 1200, 900),
                ],
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $logo = setting('site_logo');
        $hero = setting('hero_image');

        $this->assertIsString($logo);
        $this->assertStringStartsWith('site/branding/', $logo);
        $this->assertIsString($hero);
        $this->assertStringStartsWith('site/hero/', $hero);
        $this->assertTrue(Storage::disk('public')->exists($logo));
        $this->assertTrue(Storage::disk('public')->exists($hero));
        $this->assertNotEmpty(setting_array('gallery_images'));
        $this->assertSame(2, count(setting_array('gallery_images')));
    }

    public function test_admin_can_remove_uploaded_brand_images(): void
    {
        Storage::fake('public');

        app(SettingsService::class)->setMany([
            'site_logo' => 'site/branding/logo.png',
            'hero_image' => 'site/hero/hero.png',
            'gallery_images' => '["site/gallery/gallery.png"]',
        ]);

        Storage::disk('public')->put('site/branding/logo.png', 'logo');
        Storage::disk('public')->put('site/hero/hero.png', 'hero');
        Storage::disk('public')->put('site/gallery/gallery.png', 'gallery');

        $this->actingAs($this->admin())
            ->put(route('admin.settings.update'), [
                'remove_site_logo' => '1',
                'remove_hero_image' => '1',
                'remove_gallery_images' => '1',
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertSame('', setting('site_logo'));
        $this->assertSame('', setting('hero_image'));
        $this->assertSame([], setting_array('gallery_images'));
        $this->assertFalse(Storage::disk('public')->exists('site/branding/logo.png'));
        $this->assertFalse(Storage::disk('public')->exists('site/hero/hero.png'));
        Storage::disk('public')->assertMissing('site/gallery/gallery.png');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'settings_updated',
            'old_values->site_logo' => 'site/branding/logo.png',
            'old_values->hero_image' => 'site/hero/hero.png',
            'old_values->gallery_images' => '["site/gallery/gallery.png"]',
            'new_values->site_logo' => '',
            'new_values->hero_image' => '',
            'new_values->gallery_images' => '[]',
        ]);
    }

    public function test_uploaded_brand_images_override_requested_removals_in_settings_and_audit(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->put(route('admin.settings.update'), [
                'site_logo' => UploadedFile::fake()->image('logo.png'),
                'hero_image' => UploadedFile::fake()->image('hero.png'),
                'gallery_images' => [UploadedFile::fake()->image('gallery.png')],
                'remove_site_logo' => '1',
                'remove_hero_image' => '1',
                'remove_gallery_images' => '1',
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertNotEmpty(setting('site_logo'));
        $this->assertNotEmpty(setting('hero_image'));
        $this->assertCount(1, setting_array('gallery_images'));

        Storage::disk('public')->assertExists([
            setting('site_logo'),
            setting('hero_image'),
            ...setting_array('gallery_images'),
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'settings_updated',
            'new_values->site_logo' => setting('site_logo'),
            'new_values->hero_image' => setting('hero_image'),
            'new_values->gallery_images' => setting('gallery_images'),
        ]);
    }

    public function test_failed_settings_persistence_keeps_existing_brand_files_and_removes_staged_uploads(): void
    {
        Storage::fake('public');

        app(SettingsService::class)->setMany([
            'site_logo' => 'site/branding/logo.png',
            'hero_image' => 'site/hero/hero.png',
            'gallery_images' => json_encode(['site/gallery/gallery.png']),
        ]);

        Storage::disk('public')->put('site/branding/logo.png', 'logo');
        Storage::disk('public')->put('site/hero/hero.png', 'hero');
        Storage::disk('public')->put('site/gallery/gallery.png', 'gallery');
        $existingFiles = Storage::disk('public')->allFiles();

        $settings = $this->partialMock(SettingsService::class);
        $settings->shouldReceive('setMany')
            ->once()
            ->andThrow(new RuntimeException('Settings could not be persisted.'));

        $this->actingAs($this->admin());
        $this->withoutExceptionHandling();
        $this->expectException(RuntimeException::class);

        try {
            $this->put(route('admin.settings.update'), [
                'site_logo' => UploadedFile::fake()->image('new-logo.png'),
                'hero_image' => UploadedFile::fake()->image('new-hero.png'),
                'gallery_images' => [UploadedFile::fake()->image('new-gallery.png')],
            ]);
        } finally {
            $this->assertSame('site/branding/logo.png', setting('site_logo'));
            $this->assertSame('site/hero/hero.png', setting('hero_image'));
            $this->assertSame(['site/gallery/gallery.png'], setting_array('gallery_images'));
            Storage::disk('public')->assertExists([
                'site/branding/logo.png',
                'site/hero/hero.png',
                'site/gallery/gallery.png',
            ]);
            $this->assertSame($existingFiles, Storage::disk('public')->allFiles());
        }
    }

    public function test_brand_assets_render_on_the_storefront_homepage(): void
    {
        app(SettingsService::class)->setMany([
            'company_name' => 'MASR Security',
            'facebook' => 'https://www.facebook.com/profile.php?id=61586652051946',
            'phone' => '',
            'email' => '',
            'site_logo' => 'site/branding/logo.png',
            'hero_image' => 'site/hero/hero.png',
            'gallery_images' => json_encode(['site/gallery/gallery-1.png', 'site/gallery/gallery-2.png']),
            'why_points_en' => "  Security cameras, NVR/DVR recorders and accessories \r\n \t\n Interactive display solutions  ",
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('MASR Security')
            ->assertSee('/storage/site/branding/logo.png')
            ->assertSee('/storage/site/hero/hero.png')
            ->assertSee('/storage/site/gallery/gallery-1.png')
            ->assertSee('/storage/site/gallery/gallery-2.png')
            ->assertSee('Why choose us')
            ->assertSee('>Security cameras, NVR/DVR recorders and accessories</p>', false)
            ->assertSee('>Interactive display solutions</p>', false)
            ->assertSee('https://www.facebook.com/profile.php?id=61586652051946');
    }

    public function test_arabic_why_choose_us_preserves_complete_lines_and_unicode_characters(): void
    {
        app(SettingsService::class)->set('why_points_ar', "  كاميرات مراقبة ومسجلات وإكسسوارات\r\n \t\nحلول الشاشات التفاعلية\u{2028}أنظمة أمنية احترافية للمنازل والشركات  ");

        $this->withSession(['locale' => 'ar'])
            ->get(route('home'))
            ->assertSee('>كاميرات مراقبة ومسجلات وإكسسوارات</p>', false)
            ->assertSee('>حلول الشاشات التفاعلية</p>', false)
            ->assertSee('>أنظمة أمنية احترافية للمنازل والشركات</p>', false)
            ->assertDontSee("\u{FFFD}");
    }

    public function test_homepage_hides_placeholder_sections_when_unconfigured(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Security products and solutions from the Hikvision distribution channel.')
            ->assertDontSee('A look at the security systems we supply and configure.')
            ->assertDontSee('Why choose us')
            ->assertDontSee('tel:');
    }

    public function test_homepage_hides_why_choose_us_when_points_are_whitespace(): void
    {
        app(SettingsService::class)->set('why_points_en', " \r\n\t\n ");

        $this->get(route('home'))->assertDontSee('Why choose us');
    }

    public function test_storefront_and_sign_in_pages_use_the_circular_brand_favicon(): void
    {
        foreach (['home', 'shop', 'login', 'register'] as $routeName) {
            $this->get(route($routeName))
                ->assertSee('rel="icon" type="image/svg+xml" sizes="any"', false)
                ->assertSee('favicon.svg?v=masr-circle-1', false);
        }
    }

    public function test_admin_and_customer_pages_use_the_same_brand_favicon(): void
    {
        $this->actingAs($this->admin());

        foreach (['admin.dashboard', 'account.dashboard'] as $routeName) {
            $this->get(route($routeName))
                ->assertSee('favicon.svg?v=masr-circle-1', false);
        }
    }
}
