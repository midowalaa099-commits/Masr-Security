<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MediaStorage;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
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

    public function test_settings_changes_replace_values_already_read_in_the_same_request(): void
    {
        $settings = app(SettingsService::class);
        $settings->set('company_name', 'Before');
        $this->assertSame('Before', $settings->get('company_name'));

        $settings->set('company_name', 'After');

        $this->assertSame('After', $settings->get('company_name'));
        $this->assertDatabaseHas('settings', ['key' => 'company_name', 'value' => 'After']);
    }

    public function test_admin_can_upload_logo_hero_and_gallery_images(): void
    {
        Storage::fake('public');
        Storage::fake('s3');

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
        $this->assertStringStartsWith('supabase/site/branding/', $logo);
        $this->assertIsString($hero);
        $this->assertStringStartsWith('supabase/site/hero/', $hero);
        Storage::disk('s3')->assertExists($logo);
        Storage::disk('s3')->assertExists($hero);
        $gallery = setting_array('gallery_images');
        $this->assertCount(2, $gallery);
        Storage::disk('s3')->assertExists($gallery);
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
        Storage::fake('s3');

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

        Storage::disk('s3')->assertExists([
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
        Storage::fake('s3');

        app(SettingsService::class)->setMany([
            'site_logo' => 'site/branding/logo.png',
            'hero_image' => 'site/hero/hero.png',
            'gallery_images' => json_encode(['site/gallery/gallery.png']),
        ]);

        Storage::disk('public')->put('site/branding/logo.png', 'logo');
        Storage::disk('public')->put('site/hero/hero.png', 'hero');
        Storage::disk('public')->put('site/gallery/gallery.png', 'gallery');
        $existingFiles = Storage::disk('public')->allFiles();
        $realMedia = app(MediaStorage::class);
        $stagedPaths = [];
        $cleanupAttempts = 0;

        $media = $this->partialMock(MediaStorage::class);
        $media->shouldReceive('store')->andReturnUsing(function (UploadedFile $file, string $directory) use ($realMedia, &$stagedPaths): string {
            $path = $realMedia->store($file, $directory);
            $stagedPaths[] = $path;

            return $path;
        });
        $media->shouldReceive('delete')->andReturnUsing(function (string $path) use ($realMedia, &$stagedPaths, &$cleanupAttempts): void {
            if ($path === ($stagedPaths[0] ?? null)) {
                $cleanupAttempts++;

                throw new RuntimeException('Object cleanup failed.');
            }

            $realMedia->delete($path);
        });
        Log::spy();

        $settings = $this->partialMock(SettingsService::class);
        $settings->shouldReceive('setMany')
            ->once()
            ->andThrow(new RuntimeException('Settings could not be persisted.'));

        $this->actingAs($this->admin());
        $this->withoutExceptionHandling();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Settings could not be persisted.');

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
            Storage::disk('s3')->assertExists($stagedPaths[0]);
            foreach (array_slice($stagedPaths, 1) as $path) {
                Storage::disk('s3')->assertMissing($path);
            }
            $this->assertSame(3, $cleanupAttempts);
            Log::shouldHaveReceived('error')
                ->once()
                ->with('settings.media_delete_failed', \Mockery::on(
                    static fn (array $context): bool => $context['path'] === $stagedPaths[0],
                ));
        }
    }

    public function test_failed_old_media_deletion_is_reported_and_retried_without_failing_settings_update(): void
    {
        Storage::fake('public');
        Storage::fake('s3');

        $oldPaths = [
            'supabase/site/branding/old-logo.png',
            'supabase/site/hero/old-hero.png',
            'supabase/site/gallery/old-gallery.png',
        ];
        app(SettingsService::class)->setMany([
            'site_logo' => $oldPaths[0],
            'hero_image' => $oldPaths[1],
            'gallery_images' => json_encode([$oldPaths[2]]),
        ]);

        Storage::disk('s3')->put($oldPaths[0], 'logo');
        Storage::disk('s3')->put($oldPaths[1], 'hero');
        Storage::disk('s3')->put($oldPaths[2], 'gallery');

        $realMedia = app(MediaStorage::class);
        $failedDeleteAttempts = 0;
        $media = $this->partialMock(MediaStorage::class);
        $media->shouldReceive('store')->andReturnUsing(
            fn (UploadedFile $file, string $directory): string => $realMedia->store($file, $directory),
        );
        $media->shouldReceive('delete')->andReturnUsing(function (string $path) use ($realMedia, $oldPaths, &$failedDeleteAttempts): void {
            if ($path === $oldPaths[0]) {
                $failedDeleteAttempts++;

                throw new RuntimeException('Old logo deletion failed.');
            }

            $realMedia->delete($path);
        });
        Log::spy();

        $this->actingAs($this->admin())
            ->put(route('admin.settings.update'), [
                'site_logo' => UploadedFile::fake()->image('new-logo.png'),
                'hero_image' => UploadedFile::fake()->image('new-hero.png'),
                'gallery_images' => [UploadedFile::fake()->image('new-gallery.png')],
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertSame(3, $failedDeleteAttempts);
        Storage::disk('s3')->assertExists($oldPaths[0]);
        Storage::disk('s3')->assertMissing([$oldPaths[1], $oldPaths[2]]);
        Storage::disk('s3')->assertExists([
            setting('site_logo'),
            setting('hero_image'),
            ...setting_array('gallery_images'),
        ]);
        Log::shouldHaveReceived('error')
            ->once()
            ->with('settings.media_delete_failed', \Mockery::on(
                static fn (array $context): bool => $context['path'] === $oldPaths[0],
            ));
    }

    public function test_supabase_brand_asset_paths_resolve_to_the_public_url(): void
    {
        Storage::fake('s3', ['url' => 'https://cdn.example.test']);

        app(SettingsService::class)->setMany([
            'site_logo' => 'supabase/site/branding/logo.png',
            'hero_image' => 'supabase/site/hero/hero.png',
        ]);

        $this->get(route('home'))
            ->assertSee('https://cdn.example.test/supabase/site/branding/logo.png')
            ->assertSee('https://cdn.example.test/supabase/site/hero/hero.png');
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

    public function test_public_brand_assets_render_without_a_storage_prefix(): void
    {
        app(SettingsService::class)->setMany([
            'site_logo' => '/images/branding/masr-security-logo.jpg',
            'hero_image' => '/images/branding/masr-security-hero.jpg',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('/images/branding/masr-security-logo.jpg')
            ->assertSee('/images/branding/masr-security-hero.jpg')
            ->assertDontSee('/storage/images/branding/');
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
