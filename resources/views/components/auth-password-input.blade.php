@props(['id', 'name', 'autocomplete', 'variant' => 'auth', 'showStrength' => false])

<div
    x-data="{
        showPassword: false,
        password: '',
        get hasLength() { return this.password.length >= 8 },
        get hasMixedCase() { return /[a-z]/.test(this.password) && /[A-Z]/.test(this.password) },
        get hasNumber() { return /\d/.test(this.password) },
        get hasSymbol() { return /[^A-Za-z0-9]/.test(this.password) },
        get meetsRequirements() { return this.hasLength && this.hasMixedCase && this.hasNumber && this.hasSymbol },
    }"
    class="mt-1"
>
    <div class="relative">
        <x-text-input
            :variant="$variant"
            :id="$id"
            :name="$name"
            :autocomplete="$autocomplete"
            type="password"
            x-bind:type="showPassword ? 'text' : 'password'"
            x-model="password"
            class="block w-full pe-12"
            required
        />
        <button
            type="button"
            x-on:click="showPassword = !showPassword"
            x-bind:aria-label='showPassword ? @js(__('auth_pages.hide_password')) : @js(__('auth_pages.show_password'))'
            x-bind:aria-pressed="showPassword.toString()"
            class="absolute inset-y-0 end-1.5 my-1.5 inline-flex w-9 items-center justify-center rounded-lg text-slate-500 transition hover:bg-brand-50 hover:text-brand-700 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-brand-500"
        >
            <svg x-show="!showPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12 18.25 18.75 12 18.75 2.25 12 2.25 12Z" />
                <circle cx="12" cy="12" r="2.75" />
            </svg>
            <svg x-show="showPassword" style="display: none" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3 3l18 18M10.6 5.37A10.9 10.9 0 0 1 12 5.25c6.25 0 9.75 6.75 9.75 6.75a14.7 14.7 0 0 1-2.7 3.38M6.02 6.02C3.57 8.07 2.25 12 2.25 12S5.75 18.75 12 18.75c1.64 0 3.1-.46 4.39-1.16" />
                <path d="M9.88 9.88a3 3 0 0 0 4.24 4.24" />
            </svg>
        </button>
    </div>

    @if ($showStrength)
        <p
            class="mt-2 text-xs leading-5 text-slate-500"
            x-bind:class="meetsRequirements ? 'text-emerald-700' : 'text-slate-500'"
            x-text="meetsRequirements ? @js(__('auth_pages.password_requirements_met')) : @js(__('auth_pages.password_requirements_short'))"
            aria-live="polite"
        >{{ __('auth_pages.password_requirements_short') }}</p>
    @endif
</div>
