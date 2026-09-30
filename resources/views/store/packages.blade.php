<x-store.layout>

    <section class="relative isolate overflow-hidden bg-navy-950">
        <div class="absolute -end-16 -top-28 -z-10 h-80 w-80 rounded-full bg-brand-500/20 blur-3xl" aria-hidden="true"></div>
        <div class="mx-auto flex max-w-7xl items-end justify-between gap-4 px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
            <div>
                <span class="ui-eyebrow text-brand-300">{{ __('store.shop') }}</span>
                <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl">{{ __('store.featured_packages') }}</h1>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-300 sm:text-base">{{ __('store.packages_desc') }}</p>
            </div>
            <a href="{{ route('shop') }}" class="hidden shrink-0 rounded-xl border border-white/20 bg-white/5 px-4 py-3 text-sm font-bold text-white transition hover:bg-white/10 sm:inline-flex">{{ __('store.shop_now') }}</a>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-12 lg:px-8">
        @if ($packages->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white py-16 text-center">
                <svg class="h-14 w-14 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7l-9-5-9 5m18 0l-9 5m9-5v10m-18-10v5m9 0l9 5m-9-5v10m4.5-7.5L21 17" /></svg>
                <p class="mt-4 text-sm font-semibold text-slate-700">{{ __('store.no_packages') }}</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($packages as $package)
                    <x-store.package-card :package="$package" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $packages->links() }}
            </div>
        @endif
    </div>

</x-store.layout>