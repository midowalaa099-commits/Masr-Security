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
        <div class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10 sm:px-6">
            <div class="absolute inset-x-0 top-0 h-64 bg-navy-950" aria-hidden="true"></div>
            <div class="absolute -start-24 top-8 h-72 w-72 rounded-full bg-brand-500/30 blur-3xl" aria-hidden="true"></div>
            <div class="relative w-full max-w-md">
                <div class="mb-6 flex justify-center">
                    @include('components.store.brand', ['dark' => true])
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-navy-950/15 sm:p-8">
                {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
