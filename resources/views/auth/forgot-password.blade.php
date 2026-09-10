<x-guest-layout>
    <div class="mb-7 text-center">
        <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700 ring-1 ring-brand-100">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 00-9 0v3.75m-.75 10.125h10.5A2.625 2.625 0 0019.875 18V12.75a2.625 2.625 0 00-2.625-2.625H6.75a2.625 2.625 0 00-2.625 2.625V18a2.625 2.625 0 002.625 2.625z" />
            </svg>
        </span>
        <h1 class="text-2xl font-bold tracking-tight text-navy-950">{{ __('auth_pages.recover_access') }}</h1>
        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ __('auth_pages.forgot_password_intro') }}</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div>
            <x-input-label for="email" :value="__('auth_pages.email')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus dir="ltr" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-6 grid gap-4">
            <x-primary-button class="w-full justify-center bg-brand-700 py-3 hover:bg-brand-800 focus:bg-brand-800 focus:ring-brand-500">
                {{ __('auth_pages.send_reset_link') }}
            </x-primary-button>

            <a href="{{ route('login') }}" class="text-center text-sm font-semibold text-slate-500 transition hover:text-brand-700">
                {{ __('auth_pages.back_to_login') }}
            </a>
        </div>
    </form>

    <div class="mt-6 flex items-start gap-3 rounded-xl border border-sky-100 bg-sky-50 px-4 py-3 text-xs leading-relaxed text-sky-900">
        <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
        <p>{{ __('auth_pages.reset_security_note') }}</p>
    </div>
</x-guest-layout>
