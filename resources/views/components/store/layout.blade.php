<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="h-full scroll-smooth bg-navy-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-favicon />
    <title>{{ isset($title) ? $title.' — '.setting('company_name') : setting('company_name') }}</title>
    <meta name="description" content="{{ setting('hero_subtitle_'.app()->getLocale()) }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full overflow-x-hidden bg-slate-50 font-sans text-slate-800 antialiased" x-data="{ mobileOpen: false, searchOpen: false }">

    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:bg-brand-700 focus:px-4 focus:py-2 focus:text-white">
        {{ __('store.skip_to_content') }}
    </a>

    <!-- Top bar -->
    @if (setting('phone') || setting('email'))
    <div class="bg-navy-950 text-slate-300">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-2 text-xs sm:px-6 lg:px-8">
            <div class="flex items-center gap-4">
                @if (setting('phone'))
                    <a href="tel:{{ setting('phone') }}" class="flex items-center gap-1.5 hover:text-white" dir="ltr">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                        <span>{{ setting('phone') }}</span>
                    </a>
                @endif
                @if (setting('email'))
                    <a href="mailto:{{ setting('email') }}" class="flex items-center gap-1.5 hover:text-white">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                        <span>{{ setting('email') }}</span>
                    </a>
                @endif
            </div>

        </div>
    </div>
    @endif

    <!-- Main header -->
    <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 shadow-[0_10px_35px_-22px_rgba(15,23,42,0.45)] backdrop-blur-xl">
        <div class="mx-auto flex max-w-[94rem] items-center justify-between gap-4 px-4 py-3.5 sm:px-6 lg:px-8">
            <div class="flex shrink-0 items-center gap-3">
                <button @click="mobileOpen = !mobileOpen" type="button" class="-m-2 inline-flex items-center justify-center rounded-lg p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 xl:hidden" aria-label="{{ __('store.navigation') }}" :aria-expanded="mobileOpen" aria-controls="store-mobile-navigation">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                </button>
                @include('components.store.brand', ['logoClass' => 'h-16 w-16 shrink-0 rounded-full object-cover shadow-md ring-2 ring-brand-100 sm:h-20 sm:w-20'])
            </div>

            <nav class="hidden items-center gap-1.5 whitespace-nowrap rounded-2xl border border-slate-100 bg-slate-50/80 p-1.5 shadow-inner shadow-slate-200/50 xl:flex">
                <a href="{{ route('home') }}" class="rounded-xl px-4 py-2.5 text-base font-semibold transition duration-200 ease-out {{ request()->routeIs('home') ? 'bg-white text-brand-700 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:-translate-y-0.5 hover:bg-white hover:text-brand-700 hover:shadow-sm' }}">
                    {{ __('store.home') }}
                </a>
                <a href="{{ route('shop') }}" class="rounded-xl px-4 py-2.5 text-base font-semibold transition duration-200 ease-out {{ request()->routeIs('shop') || request()->routeIs('categories.show') || request()->routeIs('products.show') ? 'bg-white text-brand-700 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:-translate-y-0.5 hover:bg-white hover:text-brand-700 hover:shadow-sm' }}">
                    {{ __('store.shop') }}
                </a>

                @if ($storefrontNavigationCategories->isNotEmpty())
                    <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" @keydown.escape.window="open = false">
                        <button type="button" @click="open = !open" :aria-expanded="open" aria-haspopup="true" class="flex items-center gap-1.5 rounded-xl px-4 py-2.5 text-base font-semibold transition duration-200 ease-out {{ request()->routeIs('shop') && request()->has('category') || $storefrontNavigationCategories->contains(fn ($c) => request()->routeIs('categories.show') && optional(request()->route('category'))->id === $c->id) ? 'bg-white text-brand-700 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:-translate-y-0.5 hover:bg-white hover:text-brand-700 hover:shadow-sm' }}">
                            {{ __('store.categories') }}
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-2 opacity-0" x-transition:enter-end="translate-y-0 opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="translate-y-2 opacity-0" class="absolute start-0 top-full z-50 w-80 pt-2" @click.outside="open = false">
                            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white/95 p-2 shadow-2xl shadow-slate-900/15 backdrop-blur-xl">
                                <a href="{{ route('shop') }}" class="block rounded-xl px-4 py-3 text-base font-semibold text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">{{ __('store.all') }}</a>
                                @foreach ($storefrontNavigationCategories as $category)
                                    <div class="border-t border-slate-100">
                                        <a href="{{ route('shop', ['category' => $category->id]) }}" class="block rounded-xl px-4 py-3 text-base font-semibold text-slate-800 transition hover:bg-brand-50 hover:text-brand-700">
                                            {{ $category->trans('name') }}
                                        </a>
                                        @if ($category->children->isNotEmpty())
                                            <div class="ps-8 pb-1">
                                                @foreach ($category->children as $child)
                                                    <a href="{{ route('shop', ['category' => $child->id]) }}" class="block rounded-lg px-2 py-1.5 text-sm text-slate-500 transition hover:bg-slate-50 hover:text-brand-700">
                                                        {{ $child->trans('name') }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <a href="{{ route('packages.index') }}" class="rounded-xl px-4 py-2.5 text-base font-semibold transition duration-200 ease-out {{ request()->routeIs('packages.*') ? 'bg-white text-brand-700 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:-translate-y-0.5 hover:bg-white hover:text-brand-700 hover:shadow-sm' }}">
                    {{ __('store.packages') }}
                </a>
                <a href="{{ route('about') }}" class="rounded-xl px-4 py-2.5 text-base font-semibold transition duration-200 ease-out {{ request()->routeIs('about') ? 'bg-white text-brand-700 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:-translate-y-0.5 hover:bg-white hover:text-brand-700 hover:shadow-sm' }}">
                    {{ __('store.about') }}
                </a>
                <a href="{{ route('contact') }}" class="rounded-xl px-4 py-2.5 text-base font-semibold transition duration-200 ease-out {{ request()->routeIs('contact') ? 'bg-white text-brand-700 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:-translate-y-0.5 hover:bg-white hover:text-brand-700 hover:shadow-sm' }}">
                    {{ __('store.contact') }}
                </a>
                <a href="{{ route('quote.create') }}" class="ms-2 inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-brand-700 to-brand-600 px-5 py-2.5 text-base font-bold text-white shadow-lg shadow-brand-700/20 transition duration-200 hover:-translate-y-0.5 hover:from-brand-800 hover:to-brand-700 hover:shadow-xl">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                    {{ __('store.quote') }}
                </a>
            </nav>

            <div class="flex shrink-0 items-center gap-0.5 rounded-full border border-slate-200 bg-slate-50 p-1 shadow-sm sm:gap-1 sm:p-1.5" data-header-actions>
                <a href="{{ route('locale.switch', ['locale' => app()->getLocale() === 'ar' ? 'en' : 'ar', 'back_to' => request()->getRequestUri()]) }}"
                    class="inline-flex h-10 items-center justify-center gap-1.5 rounded-full bg-white px-2.5 text-xs font-bold text-brand-800 shadow-sm ring-1 ring-slate-200 transition hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 sm:px-3"
                    lang="{{ app()->getLocale() === 'ar' ? 'en' : 'ar' }}" dir="{{ app()->getLocale() === 'ar' ? 'ltr' : 'rtl' }}" data-language-switch>
                    <svg class="hidden h-4 w-4 sm:block" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M3 12h18M12 3c4 5 4 13 0 18M12 3c-4 5-4 13 0 18"/></svg>
                    {{ app()->getLocale() === 'ar' ? 'English' : 'العربية' }}
                </a>
                <span class="mx-0.5 h-5 w-px bg-slate-200" aria-hidden="true"></span>
                <button @click="searchOpen = !searchOpen" type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-full text-slate-600 transition hover:bg-white hover:text-brand-700 focus-visible:outline-2 focus-visible:outline-brand-600" aria-label="{{ __('store.search') }}" :aria-expanded="searchOpen" aria-controls="store-header-search">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                </button>

                @auth
                    <a href="{{ route('account.dashboard') }}" class="hidden h-10 w-10 items-center justify-center rounded-full text-slate-600 transition hover:bg-white hover:text-brand-700 focus-visible:outline-2 focus-visible:outline-brand-600 sm:inline-flex" aria-label="{{ __('store.account') }}" title="{{ __('store.account') }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </a>
                @endauth
                @guest
                    <a href="{{ route('login') }}" class="hidden h-10 items-center rounded-full px-2.5 text-xs font-semibold text-slate-600 transition hover:bg-white hover:text-brand-700 sm:inline-flex">
                        {{ __('store.login') }}
                    </a>
                    <a href="{{ route('register') }}" class="hidden h-10 items-center rounded-full bg-brand-700 px-3 text-xs font-semibold text-white shadow-sm transition hover:bg-brand-800 sm:inline-flex">
                        {{ __('store.register') }}
                    </a>
                @endguest

                <a href="{{ route('cart.index') }}" class="relative inline-flex h-10 w-10 items-center justify-center rounded-full text-slate-600 transition hover:bg-white hover:text-brand-700 focus-visible:outline-2 focus-visible:outline-brand-600" aria-label="{{ __('store.cart') }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/></svg>
                    @if ($storefrontCartCount > 0)
                        <span class="absolute -end-0.5 -top-0.5 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-600 px-1 text-[11px] font-bold text-white ring-2 ring-white" dir="ltr">{{ min($storefrontCartCount, 99) }}</span>
                    @endif
                </a>
            </div>
        </div>

        <!-- Mobile menu -->
        <div id="store-mobile-navigation" x-show="mobileOpen" x-cloak x-transition class="border-t border-slate-200 bg-white xl:hidden">
            <nav class="mx-auto max-w-7xl space-y-1 px-4 py-3">
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'bg-brand-50 text-brand-700' : 'text-slate-700' }} block rounded-lg px-3 py-2 text-sm font-medium hover:bg-slate-100">{{ __('store.home') }}</a>
                <a href="{{ route('shop') }}" class="{{ request()->routeIs('shop') ? 'bg-brand-50 text-brand-700' : 'text-slate-700' }} block rounded-lg px-3 py-2 text-sm font-medium hover:bg-slate-100">{{ __('store.shop') }}</a>
                @foreach ($storefrontNavigationCategories as $category)
                    <a href="{{ route('shop', ['category' => $category->id]) }}" class="block rounded-lg ps-6 pe-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">{{ $category->trans('name') }}</a>
                @endforeach
                <a href="{{ route('packages.index') }}" class="{{ request()->routeIs('packages.*') ? 'bg-brand-50 text-brand-700' : 'text-slate-700' }} block rounded-lg px-3 py-2 text-sm font-medium hover:bg-slate-100">{{ __('store.packages') }}</a>
                <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'bg-brand-50 text-brand-700' : 'text-slate-700' }} block rounded-lg px-3 py-2 text-sm font-medium hover:bg-slate-100">{{ __('store.about') }}</a>
                <a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? 'bg-brand-50 text-brand-700' : 'text-slate-700' }} block rounded-lg px-3 py-2 text-sm font-medium hover:bg-slate-100">{{ __('store.contact') }}</a>
                <a href="{{ route('quote.create') }}" class="mt-2 block rounded-lg bg-brand-700 px-3 py-2.5 text-center text-sm font-semibold text-white hover:bg-brand-800">{{ __('store.quote') }}</a>
                @auth
                    <a href="{{ route('account.dashboard') }}" class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">{{ __('store.account') }}</a>
                @endauth
                @guest
                    <div class="mt-2 grid grid-cols-2 gap-2 border-t border-slate-100 pt-3">
                        <a href="{{ route('login') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('store.login') }}</a>
                        <a href="{{ route('register') }}" class="rounded-lg bg-brand-700 px-3 py-2 text-center text-sm font-semibold text-white hover:bg-brand-800">{{ __('store.register') }}</a>
                    </div>
                @endguest
            </nav>
        </div>

        <!-- Search bar -->
        <div id="store-header-search" x-show="searchOpen" x-cloak x-transition class="border-t border-slate-200 bg-slate-50">
            <form action="{{ route('shop') }}" method="GET" class="mx-auto flex max-w-7xl items-center gap-2 px-4 py-3">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('store.search_placeholder') }}"
                    class="w-full flex-1 rounded-lg border-slate-300 bg-white px-4 py-2 text-sm text-slate-800 focus:border-brand-500 focus:ring-brand-500">
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    {{ __('store.search') }}
                </button>
            </form>
        </div>
    </header>

    <x-store.flash-messages />

    <main id="main">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="mt-16 bg-navy-950 text-slate-300">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-10 md:grid-cols-4">
                <div class="md:col-span-2">
                    @include('components.store.brand', ['dark' => true])
                    <p class="mt-4 max-w-md text-sm leading-relaxed text-slate-400">{{ setting('hero_subtitle_'.app()->getLocale()) }}</p>
                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        @if (setting('facebook'))
                            <a href="{{ setting('facebook') }}" target="_blank" rel="noopener" class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-slate-300 transition hover:bg-brand-600 hover:text-white">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M13.5 21v-7h2.4l.4-3h-2.8V9.1c0-.87.24-1.47 1.5-1.47h1.4V5.02a20 20 0 00-2.06-.11c-2.05 0-3.45 1.25-3.45 3.55V11H8.5v3H11v7h2.5z"/></svg>
                            </a>
                        @endif
                        @if (setting('instagram'))
                            <a href="{{ setting('instagram') }}" target="_blank" rel="noopener" class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-slate-300 transition hover:bg-brand-600 hover:text-white">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8.6a3.4 3.4 0 100 6.8 3.4 3.4 0 000-6.8zM21.5 12c0-1.3 0-2.6-.1-3.9-.1-2-1.5-3.4-3.5-3.5-1.3-.1-2.6-.1-3.9-.1s-2.6 0-3.9.1c-2 .1-3.4 1.5-3.5 3.5-.1 1.3-.1 2.6-.1 3.9s0 2.6.1 3.9c.1 2 1.5 3.4 3.5 3.5 1.3.1 2.6.1 3.9.1s2.6 0 3.9-.1c2-.1 3.4-1.5 3.5-3.5.1-1.3.1-2.6.1-3.9zm-9.5 5.8a5.8 5.8 0 115.8-5.8 5.8 5.8 0 01-5.8 5.8zm6.9-10.4a1.35 1.35 0 11-1.36-1.35 1.36 1.36 0 011.36 1.35z"/></svg>
                            </a>
                        @endif
                        @if (setting('whatsapp'))
                            <a href="https://wa.me/{{ preg_replace('/\D/', '', (string) setting('whatsapp')) }}" target="_blank" rel="noopener" class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-slate-300 transition hover:bg-green-600 hover:text-white">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a10 10 0 00-8.5 15.3L2 22l4.8-1.6A10 10 0 1012 2zm0 18.2a8.2 8.2 0 01-4.2-1.2l-.3-.2-3 .1 1-2.9-.2-.3A8.2 8.2 0 1112 20.2zm4.5-6.1c-.25-.13-1.47-.73-1.7-.81s-.4-.13-.56.13-.65.81-.8.97-.3.19-.55.06a7 7 0 01-2-1.24 7.6 7.6 0 01-1.4-1.73c-.15-.25 0-.39.11-.51s.25-.29.37-.44a1.7 1.7 0 00.25-.42.47.47 0 000-.44c-.06-.13-.56-1.36-.77-1.86s-.41-.42-.56-.43h-.48a.92.92 0 00-.66.31 2.8 2.8 0 00-.87 2.09 4.9 4.9 0 001 2.6 11.2 11.2 0 004.3 3.8 14.4 14.4 0 001.4.52c1.74.56 2 .37 2.36.35a2 2 0 001.3-.92 1.6 1.6 0 00.11-.92c-.06-.13-.2-.2-.45-.33z"/></svg>
                            </a>
                        @endif
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-white">{{ __('store.shop') }}</h3>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li><a href="{{ route('shop') }}" class="hover:text-white">{{ __('store.shop') }}</a></li>
                        <li><a href="{{ route('packages.index') }}" class="hover:text-white">{{ __('store.packages') }}</a></li>
                        @foreach ($storefrontNavigationCategories->take(4) as $category)
                            <li><a href="{{ route('shop', ['category' => $category->id]) }}" class="hover:text-white">{{ $category->trans('name') }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-white">{{ __('store.contact') }}</h3>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        @if (setting('phone'))
                            <li class="flex items-start gap-2">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                                <a href="tel:{{ setting('phone') }}" class="hover:text-white" dir="ltr">{{ setting('phone') }}</a>
                            </li>
                        @endif
                        @if (setting('whatsapp'))
                            <li class="flex items-start gap-2">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
                                <a href="https://wa.me/{{ preg_replace('/\D/', '', (string) setting('whatsapp')) }}" target="_blank" rel="noopener" class="hover:text-white">{{ __('store.contact_whatsapp') }}</a>
                            </li>
                        @endif
                        @if (setting('email'))
                            <li class="flex items-start gap-2">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                                <a href="mailto:{{ setting('email') }}" class="hover:text-white">{{ setting('email') }}</a>
                            </li>
                        @endif
                        @if (setting('address'))
                            <li class="flex items-start gap-2">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                                <span>{{ setting('address') }}</span>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>

            <div class="mt-10 border-t border-white/10 pt-6 text-center text-xs text-slate-500">
                &copy; {{ date('Y') }} {{ setting('company_name') }}. {{ __('store.all_rights_reserved') }}
            </div>
        </div>
    </footer>
</body>
</html>
