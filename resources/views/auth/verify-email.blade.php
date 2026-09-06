<x-guest-layout>
    <div class="mb-6 text-sm leading-relaxed text-slate-600">
        {{ __('auth_pages.verify_email_intro') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 text-sm font-medium text-emerald-700">
            {{ __('auth_pages.verification_link_sent') }}
        </div>
    @endif

    <div class="mt-6 flex items-center justify-between gap-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <x-primary-button class="bg-brand-700 hover:bg-brand-800 focus:bg-brand-800 focus:ring-brand-500">
                {{ __('auth_pages.resend_verification_email') }}
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="text-sm font-semibold text-brand-700 hover:text-brand-800">
                {{ __('auth_pages.logout') }}
            </button>
        </form>
    </div>
</x-guest-layout>
