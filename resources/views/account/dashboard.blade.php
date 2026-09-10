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

                <div id="account-security" class="mt-6 scroll-mt-32 overflow-hidden rounded-2xl border border-brand-100 bg-white shadow-sm">
                    <div class="flex items-center gap-4 border-b border-brand-100 bg-gradient-to-r from-brand-50 to-white px-6 py-5">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-700 text-white shadow-sm">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 00-9 0v3.75m-.75 10.125h10.5A2.625 2.625 0 0019.875 18V12.75a2.625 2.625 0 00-2.625-2.625H6.75a2.625 2.625 0 00-2.625 2.625V18a2.625 2.625 0 002.625 2.625z" /></svg>
                        </span>
                        <div>
                            <h2 class="font-bold text-navy-950">{{ __('auth_pages.account_security') }}</h2>
                            <p class="mt-0.5 text-sm text-slate-600">{{ __('auth_pages.account_security_intro') }}</p>
                        </div>
                    </div>
                    <div class="p-6">
                        @include('profile.partials.update-password-form')
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
