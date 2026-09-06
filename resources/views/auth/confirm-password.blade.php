<x-guest-layout>
    <div class="mb-6 text-sm leading-relaxed text-slate-600">
        {{ __('auth_pages.confirm_password_intro') }}
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div>
            <x-input-label for="password" :value="__('auth_pages.password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-6 flex justify-end">
            <x-primary-button class="bg-brand-700 hover:bg-brand-800 focus:bg-brand-800 focus:ring-brand-500">
                {{ __('auth_pages.confirm') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
