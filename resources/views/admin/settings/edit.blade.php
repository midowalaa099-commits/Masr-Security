<x-admin.layout title="{{ __('admin.settings') }}">

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="max-w-3xl space-y-6">
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
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('admin.brand_assets') }}</h2>
            <div>
                <x-input-label for="site_logo" :value="__('admin.site_logo')" />
                @if ($settings['site_logo'])
                    <div class="mt-2 flex flex-wrap items-center gap-3">
                        <img src="{{ Storage::disk('public')->url($settings['site_logo']) }}" alt="{{ __('admin.site_logo') }}" class="h-16 max-w-56 rounded-lg border border-slate-200 object-contain p-1">
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-600">
                            <input type="checkbox" name="remove_site_logo" value="1" class="rounded border-slate-300 text-brand-700 focus:ring-brand-500">
                            {{ __('admin.remove_logo') }}
                        </label>
                    </div>
                @endif
                <input id="site_logo" name="site_logo" type="file" accept="image/jpeg,image/png,image/webp,image/svg+xml" class="mt-2 block w-full text-sm text-slate-600 file:me-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                <p class="mt-1 text-xs text-slate-500">{{ __('admin.logo_hint') }}</p>
                <x-input-error :messages="$errors->get('site_logo')" class="mt-2" />
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
            <div>
                <x-input-label for="hero_image" :value="__('admin.hero_image')" />
                @if ($settings['hero_image'])
                    <div class="mt-2 flex flex-wrap items-center gap-3">
                        <img src="{{ Storage::disk('public')->url($settings['hero_image']) }}" alt="{{ __('admin.hero_image') }}" class="h-20 w-36 rounded-lg border border-slate-200 object-cover">
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-600"><input type="checkbox" name="remove_hero_image" value="1" class="rounded border-slate-300 text-brand-700 focus:ring-brand-500">{{ __('admin.remove_image') }}</label>
                    </div>
                @endif
                <input id="hero_image" name="hero_image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full text-sm text-slate-600 file:me-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                <x-input-error :messages="$errors->get('hero_image')" class="mt-2" />
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
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="why_points_en" :value="__('admin.why_points_en')" />
                    <textarea id="why_points_en" name="why_points_en" rows="4" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('why_points_en', $settings['why_points_en']) }}</textarea>
                    <x-input-error :messages="$errors->get('why_points_en')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="why_points_ar" :value="__('admin.why_points_ar')" />
                    <textarea id="why_points_ar" name="why_points_ar" rows="4" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('why_points_ar', $settings['why_points_ar']) }}</textarea>
                    <x-input-error :messages="$errors->get('why_points_ar')" class="mt-2" />
                </div>
            </div>
        </div>

        <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('admin.gallery') }}</h2>
            @if (setting_array('gallery_images'))
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach (setting_array('gallery_images') as $image)
                        <img src="{{ Storage::disk('public')->url($image) }}" alt="" class="aspect-square rounded-lg border border-slate-200 object-cover">
                    @endforeach
                </div>
                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-600"><input type="checkbox" name="remove_gallery_images" value="1" class="rounded border-slate-300 text-brand-700 focus:ring-brand-500">{{ __('admin.remove_gallery_images') }}</label>
            @endif
            <input id="gallery_images" name="gallery_images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple class="block w-full text-sm text-slate-600 file:me-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
            <x-input-error :messages="$errors->get('gallery_images')" class="mt-2" />
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
