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
        <main class="relative flex min-h-screen items-center justify-center overflow-x-hidden bg-gradient-to-br from-navy-950 via-brand-950 to-navy-900 px-4 py-5 sm:px-6">
            <div class="absolute -start-24 top-8 h-80 w-80 rounded-full bg-brand-500/25 blur-3xl" aria-hidden="true"></div>
            <div class="absolute -end-32 bottom-0 h-96 w-96 rounded-full bg-blue-400/20 blur-3xl" aria-hidden="true"></div>
            <div class="absolute inset-x-0 top-1/2 h-px bg-gradient-to-r from-transparent via-white/10 to-transparent" aria-hidden="true"></div>

            <a
                href="{{ route('locale.switch', ['locale' => app()->getLocale() === 'ar' ? 'en' : 'ar', 'back_to' => request()->getRequestUri()]) }}"
                class="absolute end-4 top-4 z-20 inline-flex h-10 items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 text-sm font-bold text-white shadow-lg backdrop-blur-md transition hover:-translate-y-0.5 hover:bg-white hover:text-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white sm:end-6 sm:top-5"
                lang="{{ app()->getLocale() === 'ar' ? 'en' : 'ar' }}"
                dir="{{ app()->getLocale() === 'ar' ? 'ltr' : 'rtl' }}"
            >
                <svg class="h-4 w-4" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M3 12h18M12 3c4 5 4 13 0 18M12 3c-4 5-4 13 0 18"/></svg>
                {{ app()->getLocale() === 'ar' ? 'English' : 'العربية' }}
            </a>

            <div class="relative w-full max-w-md">
                <div class="relative mb-4 flex justify-center">
                    <div class="absolute inset-x-12 top-1/2 h-px bg-gradient-to-r from-transparent via-brand-300/60 to-transparent" aria-hidden="true"></div>
                    <div class="relative rounded-full border-4 border-white/90 bg-navy-950 p-1 shadow-2xl shadow-black/40 ring-8 ring-white/5">
                        @include('components.store.brand', [
                            'dark' => true,
                            'logoClass' => 'h-20 w-20 rounded-full object-cover sm:h-24 sm:w-24',
                        ])
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-3xl border border-white/80 bg-white/98 p-5 shadow-2xl shadow-black/30 backdrop-blur sm:p-6">
                    <div class="absolute inset-x-10 top-0 h-1 rounded-b-full bg-gradient-to-r from-brand-400 via-brand-600 to-blue-400" aria-hidden="true"></div>
                    {{ $slot }}
                </div>

                <a href="{{ route('home') }}" class="mx-auto mt-4 flex w-fit items-center gap-2 rounded-full px-4 py-2 text-xs font-semibold text-slate-300 transition hover:bg-white/10 hover:text-white">
                    <svg class="h-4 w-4 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                    {{ __('auth_pages.back_to_store') }}
                </a>
            </div>
        </main>
    </body>
</html>
