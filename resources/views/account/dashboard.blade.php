<x-store.layout>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-6 lg:flex-row">
            @include('account.partials.nav')

            <div class="min-w-0 flex-1">
                <h1 class="text-2xl font-bold text-slate-900">{{ __('store.account') }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ auth()->user()->name }}</p>

                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('admin.total_orders') }}</p>
                        <p class="mt-2 text-2xl font-extrabold text-slate-900">{{ auth()->user()->orders()->count() }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('store.orders') }}</p>
                        <p class="mt-2 text-sm font-semibold text-slate-600"><a href="{{ route('account.orders') }}" class="text-brand-700 hover:text-brand-800">{{ __('store.view_all') }} →</a></p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('store.contact') }}</p>
                        <p class="mt-2"><a href="{{ route('quote.create') }}" class="text-brand-700 hover:text-brand-800">{{ __('store.quote') }}</a></p>
                    </div>
                </div>

                @if ($orders->isNotEmpty())
                    <h2 class="mt-8 text-lg font-bold text-slate-900">{{ __('store.recent_orders') }}</h2>
                    <div class="mt-4">
                        @include('account.partials.orders-table', ['orders' => $orders])
                    </div>
                @endif
            </div>
        </div>
    </div>

</x-store.layout>
