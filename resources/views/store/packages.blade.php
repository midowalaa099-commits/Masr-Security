<x-store.layout>

    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-8 sm:px-6 lg:px-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ __('store.featured_packages') }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ __('store.category_desc') }}</p>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
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