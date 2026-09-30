@props(['brands', 'value' => null])

@php
    $brandOptions = $brands->pluck('name')->all();
    $selectedBrand = (string) old('brand', $value ?? '');
    $hasLegacyBrand = $selectedBrand !== '' && ! in_array($selectedBrand, $brandOptions, true);
@endphp

<div>
    <fieldset>
        <legend class="text-sm font-semibold text-slate-700">{{ __('admin.brand') }}</legend>
        <p id="brand-help" class="mt-1 text-xs leading-relaxed text-slate-500">{{ __('admin.brand_options_hint') }}</p>

        <div class="mt-3 grid grid-cols-2 gap-2.5 xl:grid-cols-4">
            @foreach ($brandOptions as $brand)
                <label class="group relative cursor-pointer">
                    <input
                        id="brand-{{ strtolower($brand) }}"
                        type="radio"
                        name="brand"
                        value="{{ $brand }}"
                        aria-describedby="brand-help"
                        class="peer sr-only"
                        @checked($selectedBrand === $brand)
                    >
                    <span class="flex min-h-16 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-3 pe-9 transition group-hover:border-brand-300 group-hover:bg-brand-50/40 peer-checked:border-brand-500 peer-checked:bg-brand-50 peer-checked:ring-2 peer-checked:ring-brand-500/15 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-500">
                        <span class="flex min-w-0 items-center gap-2.5">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-sm font-extrabold text-slate-600 transition group-hover:bg-white group-hover:text-brand-700 peer-checked:bg-white peer-checked:text-brand-700">{{ mb_substr($brand, 0, 1) }}</span>
                            <span class="truncate text-sm font-bold text-slate-700" dir="ltr">{{ $brand }}</span>
                        </span>
                    </span>
                    <svg class="pointer-events-none absolute end-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-700 opacity-0 transition peer-checked:opacity-100" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" /></svg>
                </label>
            @endforeach

            <label class="group relative cursor-pointer">
                <input
                    id="brand-none"
                    type="radio"
                    name="brand"
                    value=""
                    aria-describedby="brand-help"
                    class="peer sr-only"
                    @checked($selectedBrand === '')
                >
                <span class="flex min-h-16 items-center gap-2 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3 py-3 pe-9 text-sm font-semibold text-slate-600 transition group-hover:border-brand-300 group-hover:bg-brand-50/40 peer-checked:border-brand-500 peer-checked:bg-brand-50 peer-checked:text-brand-800 peer-checked:ring-2 peer-checked:ring-brand-500/15 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-500">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-lg text-slate-400">—</span>
                    {{ __('admin.none') }}
                </span>
                <svg class="pointer-events-none absolute end-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-700 opacity-0 transition peer-checked:opacity-100" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" /></svg>
            </label>

            @if ($hasLegacyBrand)
                <label class="group cursor-pointer col-span-2 xl:col-span-4">
                    <input
                        id="brand-existing"
                        type="radio"
                        name="brand"
                        value="{{ $selectedBrand }}"
                        aria-describedby="brand-help"
                        class="peer sr-only"
                        checked
                    >
                    <span class="flex min-h-12 items-center gap-2 rounded-xl border border-amber-200 bg-amber-50/70 px-3 py-2 text-sm font-semibold text-amber-900 peer-checked:ring-2 peer-checked:ring-amber-400/20 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-500">
                        <span>{{ __('admin.brand_existing') }}</span>
                        <span dir="ltr">{{ $selectedBrand }}</span>
                    </span>
                </label>
            @endif
        </div>
    </fieldset>

    <x-input-error :messages="$errors->get('brand')" class="mt-2" />
</div>
