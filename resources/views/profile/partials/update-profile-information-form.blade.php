<section>
    <header>
        <h2 class="text-lg font-medium text-slate-900">
            {{ __('auth_pages.profile_information') }}
        </h2>

        <p class="mt-1 text-sm text-slate-600">
            {{ __('auth_pages.profile_information_intro') }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('auth_pages.name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('auth_pages.email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" dir="ltr" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="mt-2 text-sm text-slate-800">
                        {{ __('auth_pages.email_unverified') }}

                        <button form="send-verification" class="text-sm font-semibold text-brand-700 hover:text-brand-800">
                            {{ __('auth_pages.resend_verification_link') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sm font-medium text-emerald-700">
                            {{ __('auth_pages.verification_link_sent') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button class="bg-brand-700 hover:bg-brand-800 focus:bg-brand-800 focus:ring-brand-500">{{ __('auth_pages.save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-slate-600"
                >{{ __('auth_pages.saved') }}</p>
            @endif
        </div>
    </form>
</section>
