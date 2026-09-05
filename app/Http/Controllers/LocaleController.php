<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale)
    {
        $request->validate([
            'locale' => ['required', Rule::in(SetLocale::SUPPORTED)],
        ]);

        $target = in_array($locale, SetLocale::SUPPORTED, true) ? $locale : config('app.locale');

        $request->session()->put('locale', $target);
        app()->setLocale($target);

        $back = (string) $request->input('back_to');

        $allowed = str_starts_with($back, '/') && ! str_starts_with($back, '//');

        return $allowed
            ? redirect($back)
            : redirect()->route('home');
    }
}
