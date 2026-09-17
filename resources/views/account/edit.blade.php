<x-store.layout>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-6 lg:flex-row">
            @include('account.partials.nav')

            <div class="min-w-0 flex-1">
                <h1 class="text-2xl font-bold text-slate-900">{{ __('auth_pages.account_details') }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ __('auth_pages.account_details_intro') }}</p>

                <form method="POST" action="{{ route('account.update') }}" class="mt-6 max-w-2xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    @csrf
                    @method('PATCH')

                    <div>
                        <x-input-label for="name" :value="__('auth_pages.name')" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', auth()->user()->name)" autocomplete="name" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <x-input-label for="email" :value="__('auth_pages.email')" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', auth()->user()->email)" autocomplete="email" dir="ltr" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="phone" :value="__('store.phone')" />
                            <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" :value="old('phone', auth()->user()->phone)" autocomplete="tel" inputmode="tel" placeholder="01012345678" dir="ltr" :required="! auth()->user()->isAdmin()" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>
                    </div>

                    <div class="border-t border-slate-100 pt-5">
                        <x-input-label for="current_password" :value="__('auth_pages.confirm_identity')" />
                        <x-text-input id="current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
                        <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <x-primary-button>{{ __('auth_pages.save') }}</x-primary-button>
                        <a href="{{ route('account.password') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">{{ __('store.change_password') }} →</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-store.layout>
