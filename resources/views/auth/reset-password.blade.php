<x-guest-layout>
    <div class="mb-7 text-center">
        <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m6-3c0 7.142-3.036 12.016-9 13.5-5.964-1.484-9-6.358-9-13.5A11.952 11.952 0 0012 3c3.183 0 6.078 1.24 8.25 3.264.49.456.75 1.086.75 1.736z" /></svg>
        </span>
        <h1 class="text-2xl font-bold tracking-tight text-navy-950">{{ __('auth_pages.choose_new_password') }}</h1>
        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ __('auth_pages.choose_new_password_intro') }}</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-input-label for="email" :value="__('auth_pages.email')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" dir="ltr" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('auth_pages.password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('auth_pages.confirm_password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-6">
            <x-primary-button class="w-full justify-center bg-brand-700 py-3 hover:bg-brand-800 focus:bg-brand-800 focus:ring-brand-500">
                {{ __('auth_pages.reset_password') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
