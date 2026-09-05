<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsUpdateRequest;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;

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
    ];

    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogger $audit,
    ) {}

    public function edit()
    {
        $settings = collect(self::EDITABLE_KEYS)->mapWithKeys(
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

        $old = collect(self::EDITABLE_KEYS)->mapWithKeys(
            fn (string $key) => [$key => $this->settings->get($key, '')],
        )->all();

        $this->audit->settingsUpdated($old, $validated);

        $this->settings->setMany($validated);

        return redirect()->route('admin.settings.edit')->with('success', __('admin.settings_updated'));
    }
}
