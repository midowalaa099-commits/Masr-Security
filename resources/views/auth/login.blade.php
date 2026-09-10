<x-guest-layout>
    <div class="mb-5 text-center">
        <h1 class="text-2xl font-extrabold tracking-tight text-navy-950">{{ __('auth_pages.welcome_back') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('auth_pages.login_intro') }}</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div>
            <x-input-label for="email" :value="__('auth_pages.email')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" dir="ltr" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('auth_pages.password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-brand-700 shadow-sm focus:ring-brand-500" name="remember">
                <span class="ms-2 text-sm text-slate-600">{{ __('auth_pages.remember_me') }}</span>
            </label>
        </div>

        <div class="mt-5 space-y-3">
            <x-primary-button class="w-full justify-center rounded-xl bg-brand-700 py-3 text-sm normal-case tracking-normal shadow-lg shadow-brand-700/20 hover:bg-brand-800 focus:bg-brand-800 focus:ring-brand-500">
                {{ __('auth_pages.login') }}
                <svg class="ms-2 h-4 w-4 rtl:rotate-180" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path d="M4.167 10h11.666M11.667 5.833 15.833 10l-4.166 4.167" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </x-primary-button>

            @if (Route::has('password.request'))
                <a class="block text-center text-sm font-semibold text-brand-700 transition hover:text-brand-800" href="{{ route('password.request') }}">
                    {{ __('auth_pages.forgot_password') }}
                </a>
            @endif
        </div>
    </form>

    @if (Route::has('register'))
        <div class="mt-6 border-t border-slate-200 pt-5 text-center">
            <p class="text-sm text-slate-600">{{ __('auth_pages.no_account') }}</p>
            <a
                href="{{ route('register') }}"
                class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-brand-200 bg-brand-50 px-5 py-3 text-sm font-bold text-brand-700 transition hover:border-brand-300 hover:bg-brand-100 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
            >
                {{ __('auth_pages.create_account_now') }}
                <svg class="h-4 w-4 rtl:rotate-180" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path d="M4.167 10h11.666M11.667 5.833 15.833 10l-4.166 4.167" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
        </div>
    @endif
</x-guest-layout>
