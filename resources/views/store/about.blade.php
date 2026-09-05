<x-store.layout>

    <section class="bg-navy-950">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-extrabold text-white">{{ __('store.about') }}</h1>
            <p class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-300">
                {{ setting('hero_subtitle_'.app()->getLocale()) }}
            </p>
            <a href="{{ route('quote.create') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-brand-600 px-6 py-3 text-sm font-bold text-white transition hover:bg-brand-500">
                {{ __('store.quote') }}
            </a>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-10 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <h2 class="text-2xl font-bold text-slate-900">{{ setting('company_name') ?: 'MASR Security' }}</h2>
                <div class="mt-4 space-y-4 text-sm leading-relaxed text-slate-600">
                    <p>
                        MASR Security is an Egyptian distributor and value-added reseller for Hikvision — one of the world's
                        leading manufacturers of security cameras, NVR/DVR recorders, and smart control systems.
                    </p>
                    <p>
                        We serve homes, shops, offices, and enterprises across Egypt with genuine equipment, competitive
                        pricing, quick delivery, and technical support. Every system is configured and tested to the highest
                        standards before it reaches you.
                    </p>
                    <p>
                        Beyond cameras and recorders we provide interactive display solutions for meeting rooms, control rooms,
                        and digital signage. Tell us about your project and we will design the perfect system for your space.
                    </p>
                </div>
            </div>

            <div class="space-y-4">
                @foreach ([
                    ['title' => 'Genuine products', 'desc' => 'Authorized channel equipment with full warranty.'],
                    ['title' => 'Fast nationwide delivery', 'desc' => 'Order before 2 PM for next-day dispatch.'],
                    ['title' => 'Installation support', 'desc' => 'Guidance and professional installation available.'],
                    ['title' => 'Best prices', 'desc' => 'Wholesale-direct pricing for businesses and installers.'],
                ] as $feature)
                    <div class="flex gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
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