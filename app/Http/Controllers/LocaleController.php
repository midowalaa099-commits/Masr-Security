<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale): RedirectResponse
    {
        $request->session()->put('locale', $locale);
        app()->setLocale($locale);

        $backTo = (string) $request->query('back_to');

        return $this->isLocalUrl($request, $backTo)
            ? redirect($backTo)
            : redirect()->route('home');
    }

    private function isLocalUrl(Request $request, string $url): bool
    {
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        return in_array($parts['scheme'], ['http', 'https'], true)
            && $parts['host'] === $request->getHost()
            && (! isset($parts['port']) || $parts['port'] === $request->getPort());
    }
}
