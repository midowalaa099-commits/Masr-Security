<x-store.layout>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-6 lg:flex-row">
            @include('account.partials.nav')

            <div class="min-w-0 flex-1">
                <h1 class="text-2xl font-bold text-slate-900">{{ __('store.orders') }}</h1>

                @if ($orders->isEmpty())
                    <div class="mt-6 flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white py-16 text-center">
                        <svg class="h-14 w-14 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                        <p class="mt-4 text-sm font-semibold text-slate-700">{{ __('store.no_orders_yet') }}</p>
                        <a href="{{ route('shop') }}" class="mt-3 text-sm font-bold text-brand-700 hover:text-brand-800">{{ __('store.shop_now') }}</a>
                    </div>
                @else
                    <div class="mt-6">
                        @include('account.partials.orders-table', ['orders' => $orders])
                    </div>
                    <div class="mt-8">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

</x-store.layout>