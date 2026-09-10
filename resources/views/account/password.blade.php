<div>
    <!-- Always remember that you are absolutely unique. Just like everyone else. - Margaret Mead -->
</div>
<x-store.layout>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-6 lg:flex-row">
            @include('account.partials.nav')

            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-4">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand-700 text-white shadow-lg shadow-brand-700/20">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 00-9 0v3.75m-.75 10.125h10.5A2.625 2.625 0 0019.875 18V12.75a2.625 2.625 0 00-2.625-2.625H6.75a2.625 2.625 0 00-2.625 2.625V18a2.625 2.625 0 002.625 2.625z" /></svg>
                    </span>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">{{ __('store.change_password') }}</h1>
                        <p class="mt-1 text-sm text-slate-500">{{ __('auth_pages.account_security_intro') }}</p>
                    </div>
                </div>

                <div class="mt-6 overflow-hidden rounded-2xl border border-brand-100 bg-white shadow-sm">
                    <div class="border-b border-brand-100 bg-gradient-to-r from-brand-50 to-white px-6 py-5">
                        <h2 class="font-bold text-navy-950">{{ __('auth_pages.account_security') }}</h2>
                        <p class="mt-0.5 text-sm text-slate-600">{{ __('auth_pages.update_password_intro') }}</p>
                    </div>
                    <div class="p-6">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-store.layout>
