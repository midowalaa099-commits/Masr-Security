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
    <body class="min-h-full bg-navy-950 font-sans text-slate-800 antialiased">
        <main class="relative min-h-screen overflow-x-hidden bg-gradient-to-br from-navy-950 via-brand-950 to-navy-900 px-4 py-5 sm:px-6 lg:h-screen lg:overflow-hidden lg:px-8">
            <div class="absolute -start-24 top-8 h-72 w-72 rounded-full bg-brand-500/25 blur-3xl" aria-hidden="true"></div>
            <div class="absolute -end-32 bottom-0 h-96 w-96 rounded-full bg-blue-400/15 blur-3xl" aria-hidden="true"></div>

            <a
                href="{{ route('locale.switch', ['locale' => app()->getLocale() === 'ar' ? 'en' : 'ar', 'back_to' => request()->getRequestUri()]) }}"
                class="absolute end-4 top-4 z-20 inline-flex h-10 items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 text-sm font-bold text-white shadow-lg backdrop-blur-md transition hover:-translate-y-0.5 hover:bg-white hover:text-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white sm:end-6 sm:top-5"
                lang="{{ app()->getLocale() === 'ar' ? 'en' : 'ar' }}"
                dir="{{ app()->getLocale() === 'ar' ? 'ltr' : 'rtl' }}"
            >
                <svg class="h-4 w-4" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M3 12h18M12 3c4 5 4 13 0 18M12 3c-4 5-4 13 0 18"/></svg>
                {{ app()->getLocale() === 'ar' ? 'English' : 'العربية' }}
            </a>

            <div class="relative mx-auto grid min-h-[calc(100vh-2.5rem)] w-full max-w-7xl items-center gap-6 lg:min-h-0 lg:h-full lg:grid-cols-[minmax(0,1.2fr)_minmax(24rem,0.8fr)] lg:gap-10">
                <div class="group relative w-full overflow-hidden rounded-3xl border border-white/15 bg-brand-950 shadow-2xl shadow-black/30">
                    <div class="pointer-events-none absolute inset-0 z-10 rounded-3xl ring-1 ring-inset ring-white/10" aria-hidden="true"></div>
                    <img
                        src="{{ asset('images/branding/masr-security-hero.jpg') }}"
                        alt="{{ __('auth_pages.security_hero_alt') }}"
                        class="h-auto w-full object-contain transition duration-700 ease-out group-hover:scale-[1.015]"
                    >
                </div>

                <div class="relative mx-auto w-full max-w-md">
                    <div class="mb-3 flex justify-center">
                        <div class="rounded-full border-4 border-white/90 bg-navy-950 p-1 shadow-xl shadow-black/35 ring-4 ring-brand-500/15">
                            @include('components.store.brand', [
                                'dark' => true,
                                'logoClass' => 'h-20 w-20 rounded-full object-cover sm:h-24 sm:w-24',
                            ])
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-3xl border border-white/70 bg-white/98 p-5 shadow-2xl shadow-black/25 backdrop-blur sm:p-6">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>
