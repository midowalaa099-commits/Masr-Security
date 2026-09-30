<x-store.layout>

    <section class="relative isolate overflow-hidden bg-navy-950">
        <div class="absolute -end-20 -top-32 -z-10 h-96 w-96 rounded-full bg-brand-500/20 blur-3xl" aria-hidden="true"></div>
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20">
            <span class="ui-eyebrow text-brand-300">{{ setting('company_name') ?: 'MASR Security' }}</span>
            <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl">{{ __('store.about') }}</h1>
            <p class="mt-4 max-w-2xl text-sm leading-relaxed text-slate-300 sm:text-base">
                {{ setting('hero_subtitle_'.app()->getLocale()) }}
            </p>
            <a href="{{ route('quote.create') }}" class="ui-button-primary mt-7">
                {{ __('store.quote') }}
            </a>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-10 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <h2 class="text-2xl font-bold text-slate-900">
                    {{ app()->getLocale() === 'ar' ? __('store.about_company_name') : (setting('company_name') ?: __('store.about_company_name')) }}
                </h2>
                <div class="mt-4 space-y-4 text-sm leading-relaxed text-slate-600">
                    <p>{{ __('store.about_company_intro') }}</p>
                    <p>{{ __('store.about_company_service') }}</p>
                    <p>{{ __('store.about_company_solutions') }}</p>
                </div>
            </div>

            <div class="space-y-4">
                @foreach ([
                    ['title' => __('store.about_genuine_title'), 'desc' => __('store.about_genuine_desc')],
                    ['title' => __('store.about_delivery_title'), 'desc' => __('store.about_delivery_desc')],
                    ['title' => __('store.about_installation_title'), 'desc' => __('store.about_installation_desc')],
                    ['title' => __('store.about_prices_title'), 'desc' => __('store.about_prices_desc')],
                ] as $feature)
                    <div class="ui-panel flex gap-4 p-5">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">{{ $feature['title'] }}</h3>
                            <p class="mt-1 text-xs leading-relaxed text-slate-500">{{ $feature['desc'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

</x-store.layout>
