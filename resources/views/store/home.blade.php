<x-store.layout>

    <!-- Hero -->
    <section class="relative overflow-hidden bg-navy-950">
        <div class="absolute inset-0" aria-hidden="true">
            <div class="absolute -start-32 -top-32 h-96 w-96 rounded-full bg-brand-600/30 blur-3xl"></div>
            <div class="absolute -end-24 bottom-0 h-80 w-80 rounded-full bg-brand-500/20 blur-3xl"></div>
            <svg class="absolute inset-0 h-full w-full text-white/[0.03]" fill="none" stroke="currentColor" stroke-width="1">
                <pattern id="hero-grid" x="0" y="0" width="48" height="48" patternUnits="userSpaceOnUse">
                    <path d="M48 0H0v48" />
                </pattern>
                <rect width="100%" height="100%" fill="url(#hero-grid)" />
            </svg>
        </div>

        <div class="relative mx-auto grid max-w-[96rem] items-center gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)] lg:gap-12 lg:px-8 lg:py-20 xl:px-16">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full border border-brand-400/40 bg-brand-500/10 px-3 py-1 text-xs font-semibold text-brand-200">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                    {{ setting('company_name') ?: 'MASR Security' }}
                </span>

                <h1 class="mt-5 text-3xl font-extrabold leading-tight tracking-tight text-white sm:text-4xl lg:text-5xl">
                    {{ setting('hero_title_'.app()->getLocale()) ?: 'Professional Security Solutions' }}
                </h1>
                <p class="mt-4 max-w-xl text-base leading-relaxed text-slate-300 sm:text-lg">
                    {{ setting('hero_subtitle_'.app()->getLocale()) }}
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('shop') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-brand-900/40 transition hover:bg-brand-500">
                        {{ __('store.shop_now') }}
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                    </a>
                    <a href="{{ route('packages.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/5 px-6 py-3 text-sm font-bold text-white backdrop-blur transition hover:bg-white/10">
                        {{ __('store.packages') }}
                    </a>
                </div>

                <dl class="mt-10 grid max-w-md grid-cols-3 gap-6">
                    <div>
                        <dt class="text-2xl font-extrabold text-white">{{ __('store.stat_original_value') }}</dt>
                        <dd class="mt-1 text-xs text-slate-400">{{ __('store.stat_original') }}</dd>
                    </div>
                    <div>
                        <dt class="text-2xl font-extrabold text-white">{{ __('store.stat_warranty_value') }}</dt>
                        <dd class="mt-1 text-xs text-slate-400">{{ __('store.stat_warranty') }}</dd>
                    </div>
                    <div>
                        <dt class="text-2xl font-extrabold text-white">{{ __('store.stat_delivery_value') }}</dt>
                        <dd class="mt-1 text-xs text-slate-400">{{ __('store.stat_delivery') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="min-w-0">
                @if (setting('hero_image'))
                    <div class="relative isolate mx-auto w-full">
                        <div class="absolute -inset-4 -z-10 rounded-full bg-brand-500/20 blur-3xl" aria-hidden="true"></div>
                        <div class="rounded-2xl border border-white/20 bg-white/5 p-1.5 shadow-2xl shadow-brand-950/50 sm:rounded-3xl sm:p-2">
                            <img src="{{ Storage::disk('public')->url(setting('hero_image')) }}" alt="{{ setting('hero_title_'.app()->getLocale()) }}" class="block h-auto w-full rounded-xl object-contain sm:rounded-2xl" fetchpriority="high">
                        </div>
                    </div>
                @elseif (($featuredProducts ?? null) && $featuredProducts->first()?->firstImageUrl())
                    <div class="relative mx-auto hidden max-w-md rotate-2 rounded-3xl border border-white/10 bg-white/5 p-3 shadow-2xl backdrop-blur lg:block">
                        <img src="{{ $featuredProducts->first()->firstImageUrl() }}" alt="{{ $featuredProducts->first()->trans('name') }}"
                            class="aspect-[4/3] w-full rounded-2xl object-cover">
                        <div class="absolute -bottom-4 -start-4 rounded-2xl bg-white px-4 py-3 shadow-xl">
                            <span class="block text-xs text-slate-400">{{ $featuredProducts->first()->trans('name') }}</span>
                            <span class="text-lg font-extrabold text-brand-800">{{ money($featuredProducts->first()->displayPrice()) }}</span>
                        </div>
                    </div>
                @else
                    <div class="relative mx-auto hidden max-w-md rounded-3xl border border-white/10 bg-gradient-to-br from-brand-600/30 to-brand-900/40 p-8 backdrop-blur lg:block">
                        <svg class="mx-auto h-32 w-32 text-white/60" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                        <p class="mt-4 text-center text-sm text-slate-300">{{ __('store.hero_tagline') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <!-- Category tiles -->
    @if ($categories->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900">{{ __('store.shop_by_category') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ __('store.category_desc') }}</p>
                </div>
                <a href="{{ route('shop') }}" class="hidden text-sm font-semibold text-brand-700 hover:text-brand-800 sm:inline">
                    {{ __('store.view_all') }} →
                </a>
            </div>

            <div class="mt-8 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($categories as $category)
                    <a href="{{ route('shop', ['category' => $category->id]) }}"
                        class="group flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-brand-300 hover:shadow-md">
                        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700 group-hover:bg-brand-600 group-hover:text-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" /></svg>
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-bold text-slate-800 group-hover:text-brand-700">{{ $category->trans('name') }}</span>
                            <span class="mt-0.5 block text-xs text-slate-400">{{ $category->products_count ?? '' }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <!-- Featured products -->
    @if ($featuredProducts->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pb-14 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900">{{ __('store.featured_products') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ __('store.featured_desc') }}</p>
                </div>
                <a href="{{ route('shop') }}" class="hidden text-sm font-semibold text-brand-700 hover:text-brand-800 sm:inline">{{ __('store.view_all') }} →</a>
            </div>

            <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($featuredProducts as $product)
                    <x-store.product-card :product="$product" />
                @endforeach
            </div>
        </section>
    @endif

    <!-- CTA strip -->
    <section class="bg-brand-700">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-6 px-4 py-10 sm:px-6 md:flex-row lg:px-8">
            <div class="text-center md:text-start">
                <h2 class="text-2xl font-bold text-white">{{ __('store.cta_title') }}</h2>
                <p class="mt-1 text-sm text-brand-100">{{ __('store.cta_desc') }}</p>
            </div>
            <a href="{{ route('quote.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-6 py-3 text-sm font-bold text-brand-700 shadow-lg transition hover:bg-brand-50">
                {{ __('store.quote') }}
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
            </a>
        </div>
    </section>

    <!-- Featured packages -->
    @if ($featuredPackages->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900">{{ __('store.featured_packages') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ __('store.packages_desc') }}</p>
                </div>
                <a href="{{ route('packages.index') }}" class="hidden text-sm font-semibold text-brand-700 hover:text-brand-800 sm:inline">{{ __('store.view_all') }} →</a>
            </div>

            <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($featuredPackages as $package)
                    <x-store.package-card :package="$package" />
                @endforeach
            </div>
        </section>
    @endif

    <!-- Displays -->
    @if ($displays->isNotEmpty())
        <section class="bg-slate-100">
            <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900">{{ __('store.displays_title') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ __('store.displays_desc') }}</p>
                    </div>
                    <a href="{{ route('shop') }}" class="hidden text-sm font-semibold text-brand-700 hover:text-brand-800 sm:inline">{{ __('store.view_all') }} →</a>
                </div>

                <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($displays as $product)
                        <x-store.product-card :product="$product" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @php
        $whyPoints = collect(preg_split('/\R/u', (string) setting('why_points_'.app()->getLocale()), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn (string $point) => trim($point))
            ->filter();
    @endphp

    @if ($whyPoints->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-slate-900">{{ __('store.why_choose_us') }}</h2>
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                @foreach ($whyPoints as $point)
                    <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-brand-100 text-brand-700">✓</span>
                        <p class="text-sm font-medium leading-relaxed text-slate-700">{{ $point }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if (setting_array('gallery_images'))
        <section class="bg-slate-100">
            <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach (setting_array('gallery_images') as $image)
                        <img src="{{ Storage::disk('public')->url($image) }}" alt="" class="aspect-square w-full rounded-2xl object-cover shadow-sm">
                    @endforeach
                </div>
            </div>
        </section>
    @endif

</x-store.layout>
