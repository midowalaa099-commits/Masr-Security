<x-guest-layout>
    <div class="mb-6 text-sm leading-relaxed text-slate-600">
        {{ __('auth_pages.forgot_password_intro') }}
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div>
            <x-input-label for="email" :value="__('auth_pages.email')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus dir="ltr" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-6 flex justify-end">
            <x-primary-button class="bg-brand-700 hover:bg-brand-800 focus:bg-brand-800 focus:ring-brand-500">
                {{ __('auth_pages.send_reset_link') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
