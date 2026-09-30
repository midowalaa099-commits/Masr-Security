<x-store.layout>
    <section class="relative isolate overflow-hidden bg-navy-950 text-white">
        <div aria-hidden="true" class="pointer-events-none absolute -end-24 -top-48 -z-10 h-[34rem] w-[34rem] rounded-full border-[70px] border-white/[0.035]"></div>
        <div aria-hidden="true" class="pointer-events-none absolute -bottom-48 start-1/3 -z-10 h-96 w-96 rounded-full bg-brand-500/20 blur-3xl"></div>
        <div class="mx-auto grid max-w-[94rem] gap-8 px-4 py-12 sm:px-6 sm:py-16 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end lg:px-8 lg:py-20">
            <div class="max-w-3xl">
                <nav aria-label="{{ __('store.shop') }}" class="mb-6 flex items-center gap-2 text-xs font-semibold text-slate-400">
                    <a href="{{ route('home') }}" class="transition hover:text-white">{{ __('store.home') }}</a>
                    <svg class="h-3.5 w-3.5 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" /></svg>
                    <span class="text-brand-200">{{ __('store.shop') }}</span>
                </nav>
                <p class="mb-3 text-xs font-bold uppercase tracking-[0.22em] text-brand-300">{{ __('store.shop_eyebrow') }}</p>
                <h1 class="text-4xl font-black tracking-tight sm:text-5xl lg:text-6xl">{{ $section === 'all' ? __('store.shop_title') : $sections[$section] }}</h1>
                <p class="mt-5 max-w-2xl text-sm leading-7 text-slate-300 sm:text-base">{{ __('store.shop_intro') }}</p>
                @if (request('q'))
                    <p class="mt-5 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3.5 py-2 text-sm text-slate-200">
                        <svg class="h-4 w-4 text-brand-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path stroke-linecap="round" stroke-linejoin="round" d="m20 20-4-4" /></svg>
                        {{ __('store.searching_for') }} <span class="font-bold text-white">“{{ request('q') }}”</span>
                    </p>
                @endif
            </div>

            <div class="flex items-center gap-4 rounded-2xl border border-white/10 bg-white/[0.06] p-4 backdrop-blur-sm sm:min-w-64 sm:p-5">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand-500/20 text-brand-200 ring-1 ring-inset ring-brand-300/20">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                </span>
                <span>
                    <span class="block text-2xl font-black tabular-nums">{{ number_format($products->total()) }}</span>
                    <span class="mt-0.5 block text-xs font-medium text-slate-400">{{ $products->total() === 1 ? __('store.item') : __('store.items') }}</span>
                </span>
            </div>
        </div>
    </section>

    <div class="mx-auto grid max-w-[94rem] gap-6 px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
        <nav aria-label="{{ __('store.shop') }}" class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-2 sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-0 sm:pb-0">
            @foreach ($sections as $key => $label)
                <a href="{{ route('shop', array_merge(request()->only(['q', 'min_price', 'max_price', 'sort']), ['section' => $key])) }}"
                    @if ($section === $key) aria-current="page" @endif
                    @class([
                        'inline-flex shrink-0 items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-bold transition duration-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600',
                        'border-brand-700 bg-brand-700 text-white shadow-lg shadow-brand-900/10' => $section === $key,
                        'border-slate-200 bg-white text-slate-600 hover:-translate-y-0.5 hover:border-brand-200 hover:bg-brand-50 hover:text-brand-800' => $section !== $key,
                    ])>
                    @if ($section === $key)
                        <span class="h-1.5 w-1.5 rounded-full bg-white"></span>
                    @endif
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        <div class="grid gap-6 lg:grid-cols-[17rem_minmax(0,1fr)] xl:grid-cols-[18rem_minmax(0,1fr)]">
            <aside>
                <form method="GET" action="{{ route('shop') }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/[0.03]">
                    <input type="hidden" name="section" value="{{ $section }}">
                    <input type="hidden" name="sort" value="{{ request('sort', 'newest') }}">
                    @if (request('q'))
                        <input type="hidden" name="q" value="{{ request('q') }}">
                    @endif

                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" /></svg>
                            </span>
                            <h2 class="text-sm font-extrabold text-slate-900">{{ __('store.refine_by') }}</h2>
                        </div>
                        @if (request()->hasAny(['category', 'min_price', 'max_price']))
                            <a href="{{ route('shop', request()->except(['category', 'min_price', 'max_price', 'page'])) }}" class="text-xs font-bold text-brand-700 hover:text-brand-900">{{ __('store.clear_filters') }}</a>
                        @endif
                    </div>

                    <div class="grid gap-6 p-5">
                        <fieldset>
                            <legend class="mb-3 text-xs font-extrabold uppercase tracking-wider text-slate-500">{{ __('store.categories') }}</legend>
                            <div class="grid gap-1">
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-slate-600 transition hover:bg-slate-50 has-[:checked]:bg-brand-50 has-[:checked]:font-bold has-[:checked]:text-brand-800">
                                    <input type="radio" name="category" value="" @checked(! request('category')) class="h-4 w-4 border-slate-300 text-brand-700 focus:ring-brand-500">
                                    <span>{{ __('store.all') }}</span>
                                </label>
                                @foreach ($categories as $category)
                                    @include('store.shop-category', ['category' => $category])
                                @endforeach
                            </div>
                        </fieldset>

                        <fieldset class="border-t border-slate-100 pt-5">
                            <legend class="mb-3 text-xs font-extrabold uppercase tracking-wider text-slate-500">{{ __('store.price_range') }}</legend>
                            <div class="grid grid-cols-2 gap-2.5">
                                <label class="grid gap-1.5 text-[11px] font-semibold text-slate-500">
                                    {{ __('store.min_price') }}
                                    <input type="number" name="min_price" min="0" step="0.01" value="{{ request('min_price') }}" placeholder="0" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-semibold text-slate-800 transition focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10" dir="ltr">
                                </label>
                                <label class="grid gap-1.5 text-[11px] font-semibold text-slate-500">
                                    {{ __('store.max_price') }}
                                    <input type="number" name="max_price" min="0" step="0.01" value="{{ request('max_price') }}" placeholder="—" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-semibold text-slate-800 transition focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10" dir="ltr">
                                </label>
                            </div>
                            <p class="mt-2 text-[11px] text-slate-400">{{ __('store.currency_egp') }}</p>
                        </fieldset>
                    </div>

                    <div class="border-t border-slate-100 bg-slate-50/70 p-4">
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-700 px-4 py-3 text-sm font-extrabold text-white shadow-sm transition hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75 10.5 18l9-12" /></svg>
                            {{ __('store.filter') }}
                        </button>
                    </div>
                </form>
            </aside>

            <section aria-label="{{ __('store.shop') }}" class="min-w-0">
                <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-900/[0.02] sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <div class="min-w-0">
                        <p class="text-sm font-extrabold text-slate-900">
                            @if (request('q'))
                                {{ __('store.search_results_for') }} <span class="text-brand-700">“{{ request('q') }}”</span>
                            @else
                                {{ $sections[$section] }}
                            @endif
                        </p>
                        <p class="mt-1 text-xs text-slate-500">{{ __('store.showing_results', ['count' => number_format($products->total())]) }}</p>
                    </div>

                    <label class="flex shrink-0 items-center gap-2 text-xs font-semibold text-slate-500">
                        <span>{{ __('store.sort') }}</span>
                        <span class="relative">
                            <form method="GET" action="{{ route('shop') }}" x-data="{ value: @js(request('sort', 'newest')) }">
                                <input type="hidden" name="section" value="{{ $section }}">
                                @foreach (collect(request()->query())->except(['sort', 'page', 'section']) as $key => $val)
                                    @if (is_array($val))
                                        @foreach ($val as $v)
                                            <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                        @endforeach
                                    @else
                                        <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                                    @endif
                                @endforeach
                                <select name="sort" x-model="value" @change="$event.target.form.submit()" aria-label="{{ __('store.sort') }}" class="min-w-44 appearance-none rounded-xl border-slate-200 bg-slate-50 py-2.5 pe-9 ps-3 text-sm font-bold text-slate-700 transition hover:border-slate-300 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10">
                                    <option value="newest">{{ __('store.sort_newest') }}</option>
                                    <option value="price_asc">{{ __('store.sort_price_low') }}</option>
                                    <option value="price_desc">{{ __('store.sort_price_high') }}</option>
                                    <option value="name">{{ __('store.sort_name') }}</option>
                                </select>
                                <svg class="pointer-events-none absolute end-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                            </form>
                        </span>
                    </label>
                </div>

                @if ($products->isEmpty())
                    <div class="mt-5 flex flex-col items-center rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm">
                        <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-50 text-slate-400 ring-1 ring-slate-100">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path stroke-linecap="round" stroke-linejoin="round" d="m20 20-4-4" /></svg>
                        </span>
                        <h2 class="mt-5 text-lg font-extrabold text-slate-900">{{ __('store.no_products') }}</h2>
                        <p class="mt-2 max-w-sm text-sm leading-6 text-slate-500">{{ __('store.no_products_help') }}</p>
                        <a href="{{ route('shop', ['section' => $section]) }}" class="mt-6 inline-flex items-center justify-center rounded-xl bg-brand-700 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-brand-800">{{ __('store.clear_filters') }}</a>
                    </div>
                @else
                    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                        @foreach ($products as $product)
                            <x-store.product-card :product="$product" />
                        @endforeach
                    </div>

                    @if ($products->hasPages())
                        <div class="mt-8 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">{{ $products->links() }}</div>
                    @endif
                @endif
            </section>
        </div>
    </div>
</x-store.layout>
