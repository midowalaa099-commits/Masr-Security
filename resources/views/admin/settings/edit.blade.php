<x-admin.layout title="{{ __('admin.settings') }}">

    <form method="POST" action="{{ route('admin.settings.update') }}" class="max-w-3xl space-y-6">
        @csrf
        @method('PUT')

        <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('admin.company_name') }}</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="company_name" :value="__('admin.company_name')" />
                    <x-text-input id="company_name" name="company_name" class="mt-1 block w-full" :value="old('company_name', $settings['company_name'])" />
                    <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="shipping_fee" :value="__('admin.shipping_fee')" />
                    <x-text-input id="shipping_fee" type="number" step="0.01" min="0" name="shipping_fee" class="mt-1 block w-full" :value="old('shipping_fee', $settings['shipping_fee'])" />
                    <x-input-error :messages="$errors->get('shipping_fee')" class="mt-2" />
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="phone" :value="__('admin.phone')" />
                    <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone', $settings['phone'])" dir="ltr" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="whatsapp" :value="__('admin.whatsapp')" />
                    <x-text-input id="whatsapp" name="whatsapp" class="mt-1 block w-full" :value="old('whatsapp', $settings['whatsapp'])" dir="ltr" />
                    <x-input-error :messages="$errors->get('whatsapp')" class="mt-2" />
                </div>
            </div>
            <div>
                <x-input-label for="email" :value="__('admin.email')" />
                <x-text-input id="email" type="email" name="email" class="mt-1 block w-full" :value="old('email', $settings['email'])" dir="ltr" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="address" :value="__('admin.address_label')" />
                <x-text-input id="address" name="address" class="mt-1 block w-full" :value="old('address', $settings['address'])" />
                <x-input-error :messages="$errors->get('address')" class="mt-2" />
            </div>
        </div>

        <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('admin.hero_section') }}</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="hero_title_en" :value="__('admin.hero_title_en')" />
                    <x-text-input id="hero_title_en" name="hero_title_en" class="mt-1 block w-full" :value="old('hero_title_en', $settings['hero_title_en'])" />
                    <x-input-error :messages="$errors->get('hero_title_en')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="hero_title_ar" :value="__('admin.hero_title_ar')" />
                    <x-text-input id="hero_title_ar" name="hero_title_ar" class="mt-1 block w-full" :value="old('hero_title_ar', $settings['hero_title_ar'])" />
                    <x-input-error :messages="$errors->get('hero_title_ar')" class="mt-2" />
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="hero_subtitle_en" :value="__('admin.hero_subtitle_en')" />
                    <x-text-input id="hero_subtitle_en" name="hero_subtitle_en" class="mt-1 block w-full" :value="old('hero_subtitle_en', $settings['hero_subtitle_en'])" />
                    <x-input-error :messages="$errors->get('hero_subtitle_en')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="hero_subtitle_ar" :value="__('admin.hero_subtitle_ar')" />
                    <x-text-input id="hero_subtitle_ar" name="hero_subtitle_ar" class="mt-1 block w-full" :value="old('hero_subtitle_ar', $settings['hero_subtitle_ar'])" />
                    <x-input-error :messages="$errors->get('hero_subtitle_ar')" class="mt-2" />
                </div>
            </div>
        </div>

        <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('admin.social_links') }}</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="facebook" :value="__('admin.facebook')" />
                    <x-text-input id="facebook" name="facebook" class="mt-1 block w-full" :value="old('facebook', $settings['facebook'])" dir="ltr" />
                    <x-input-error :messages="$errors->get('facebook')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="instagram" :value="__('admin.instagram')" />
                    <x-text-input id="instagram" name="instagram" class="mt-1 block w-full" :value="old('instagram', $settings['instagram'])" dir="ltr" />
                    <x-input-error :messages="$errors->get('instagram')" class="mt-2" />
                </div>
            </div>
            <div>
                <x-input-label for="google_maps" :value="__('admin.google_maps')" />
                <x-text-input id="google_maps" name="google_maps" class="mt-1 block w-full" :value="old('google_maps', $settings['google_maps'])" dir="ltr" />
                <x-input-error :messages="$errors->get('google_maps')" class="mt-2" />
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-brand-700 px-6 py-2.5 text-sm font-bold text-white hover:bg-brand-800">{{ __('admin.save') }}</button>
            <a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-700">{{ __('admin.cancel') }}</a>
        </div>
    </form>

</x-admin.layout>