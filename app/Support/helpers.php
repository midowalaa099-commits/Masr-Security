<?php

use App\Services\SettingsService;

if (! function_exists('money')) {
    /**
     * Format an amount in EGP using the convention used across the storefront.
     */
    function money(null|int|float|string $amount): string
    {
        $value = is_string($amount) ? (float) $amount : (float) ($amount ?? 0);

        return number_format($value, 2, '.', ',').' '.__('store.currency_egp');
    }
}

if (! function_exists('setting')) {
    /**
     * Shortcut to read a site setting (cached).
     */
    function setting(string $key, mixed $default = null): mixed
    {
        $service = app(SettingsService::class);

        $normalized = str_starts_with($key, 'settings.') ? str_replace('settings.', '', $key) : $key;

        return $service->get($normalized, $default);
    }
}

if (! function_exists('setting_array')) {
    /**
     * Read a JSON-array setting safely.
     *
     * @return list<string>
     */
    function setting_array(string $key): array
    {
        $value = setting($key, '[]');

        if (! is_string($value)) {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded)
            ? array_values(array_filter($decoded, is_string(...)))
            : [];
    }
}
