<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsUpdateRequest;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class AdminSettingsController extends Controller
{
    private const EDITABLE_KEYS = [
        'company_name',
        'phone',
        'whatsapp',
        'email',
        'address',
        'shipping_fee',
        'facebook',
        'instagram',
        'google_maps',
        'hero_title_en',
        'hero_title_ar',
        'hero_subtitle_en',
        'hero_subtitle_ar',
        'why_points_en',
        'why_points_ar',
    ];

    private const MEDIA_KEYS = [
        'site_logo',
        'hero_image',
        'gallery_images',
    ];

    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogger $audit,
    ) {}

    public function edit()
    {
        $settings = collect([...self::EDITABLE_KEYS, ...self::MEDIA_KEYS])->mapWithKeys(
            fn (string $key) => [$key => $this->settings->get($key, '')],
        );

        return view('admin.settings.edit', ['settings' => $settings]);
    }

    public function update(SettingsUpdateRequest $request): RedirectResponse
    {
        $validated = collect($request->safe()->only(self::EDITABLE_KEYS))
            ->map(fn ($value) => $value === null ? '' : $value)
            ->map(fn ($value) => is_string($value) ? trim($value) : $value)
            ->all();

        $old = collect([...self::EDITABLE_KEYS, ...self::MEDIA_KEYS])->mapWithKeys(
            fn (string $key) => [$key => $this->settings->get($key, '')],
        )->all();

        $removedMedia = $this->removeRequestedFiles($request);

        $media = $this->storeUploadedFiles($request);

        $changes = [...$validated, ...$removedMedia, ...$media];

        $this->audit->settingsUpdated($old, $changes);

        $this->settings->setMany($changes);

        return redirect()->route('admin.settings.edit')->with('success', __('admin.settings_updated'));
    }

    /**
     * @return array<string, string>
     */
    private function storeUploadedFiles(SettingsUpdateRequest $request): array
    {
        $media = [];

        foreach ([
            'site_logo' => 'site/branding',
            'hero_image' => 'site/hero',
        ] as $key => $directory) {
            if ($request->hasFile($key)) {
                $this->deleteFile((string) $this->settings->get($key, ''));
                $media[$key] = $request->file($key)->store($directory, 'public');
            }
        }

        if ($request->hasFile('gallery_images')) {
            foreach (setting_array('gallery_images') as $image) {
                $this->deleteFile($image);
            }

            $media['gallery_images'] = json_encode(
                collect($request->file('gallery_images'))
                    ->map(fn ($image) => $image->store('site/gallery', 'public'))
                    ->all(),
                JSON_THROW_ON_ERROR,
            );
        }

        return $media;
    }

    /**
     * @return array{site_logo?: string, hero_image?: string, gallery_images?: string}
     */
    private function removeRequestedFiles(SettingsUpdateRequest $request): array
    {
        $media = [];

        foreach ([
            'remove_site_logo' => 'site_logo',
            'remove_hero_image' => 'hero_image',
        ] as $input => $key) {
            if ($request->boolean($input)) {
                $this->deleteFile((string) $this->settings->get($key, ''));
                $media[$key] = '';
            }
        }

        if ($request->boolean('remove_gallery_images')) {
            foreach (setting_array('gallery_images') as $image) {
                $this->deleteFile($image);
            }

            $media['gallery_images'] = json_encode([], JSON_THROW_ON_ERROR);
        }

        return $media;
    }

    private function deleteFile(string $path): void
    {
        if ($path !== '') {
            Storage::disk('public')->delete($path);
        }
    }
}
