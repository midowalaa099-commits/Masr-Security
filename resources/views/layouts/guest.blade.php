<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <x-favicon />

        <title>{{ setting('company_name') ?: 'MASR Security' }}</title>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-full bg-slate-50 font-sans text-slate-800 antialiased">
        <main class="relative min-h-screen overflow-hidden bg-gradient-to-b from-navy-950 via-navy-950 to-slate-50 px-4 py-8 sm:px-6 sm:py-10">
            <div class="absolute -start-24 top-8 h-72 w-72 rounded-full bg-brand-500/25 blur-3xl" aria-hidden="true"></div>
            <div class="absolute -end-32 top-40 h-80 w-80 rounded-full bg-blue-400/15 blur-3xl" aria-hidden="true"></div>

            <div class="relative mx-auto flex w-full max-w-5xl flex-col items-center">
                <div class="w-full overflow-hidden rounded-3xl border border-white/15 bg-brand-950 shadow-2xl shadow-navy-950/30">
                    <img
                        src="{{ asset('images/branding/masr-security-hero.jpg') }}"
                        alt="{{ __('auth_pages.security_hero_alt') }}"
                        class="h-auto w-full object-contain"
                    >
                </div>

                <div class="relative -mt-10 w-full max-w-md sm:-mt-14">
                    <div class="mb-4 flex justify-center">
                        <div class="rounded-full border-4 border-white bg-navy-950 p-1.5 shadow-xl shadow-navy-950/35">
                            @include('components.store.brand', [
                                'dark' => true,
                                'logoClass' => 'h-24 w-24 rounded-full object-cover sm:h-28 sm:w-28',
                            ])
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-navy-950/15 sm:p-8">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>
