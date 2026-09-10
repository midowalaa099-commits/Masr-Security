<x-guest-layout>
    <div class="mb-5 text-center">
        <h1 class="text-2xl font-extrabold tracking-tight text-navy-950">{{ __('auth_pages.join_masr_security') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('auth_pages.register_intro') }}</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div>
            <x-input-label for="name" :value="__('auth_pages.name')" />
            <x-text-input id="name" class="mt-1 block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="email" :value="__('auth_pages.email')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" dir="ltr" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('auth_pages.password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('auth_pages.confirm_password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-5 space-y-3">
            <x-primary-button class="w-full justify-center rounded-xl bg-brand-700 py-3 text-sm normal-case tracking-normal shadow-lg shadow-brand-700/20 hover:bg-brand-800 focus:bg-brand-800 focus:ring-brand-500">
                {{ __('auth_pages.register') }}
            </x-primary-button>

            <a class="block rounded-xl border border-slate-200 px-5 py-3 text-center text-sm font-bold text-brand-700 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-800" href="{{ route('login') }}">
                {{ __('auth_pages.already_registered') }} {{ __('auth_pages.login') }}
            </a>
        </div>
    </form>
</x-guest-layout>
