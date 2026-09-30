<x-admin.layout title="{{ __('admin.packages') }}">
    <x-admin.page-heading :title="__('admin.add_new').' · '.__('admin.packages')" :description="__('admin.package_create_intro')" />

    @php
        $statuses = \App\Enums\ProductStatus::cases();
    @endphp

    <form method="POST" action="{{ route('admin.packages.store') }}" enctype="multipart/form-data"
        class="mx-auto max-w-6xl space-y-6">
        @csrf

        <div class="ui-panel space-y-6 rounded-3xl p-5 sm:p-8">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-5">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-50 text-brand-700"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9.75m-9 5.25L3 16.5m9 5.25v-11m0 0l-9-5.25m9 5.25l9-5.25" /></svg></span>
                <div><h2 class="text-base font-bold text-slate-900">{{ __('admin.package_info') }}</h2><p class="mt-0.5 text-xs text-slate-500">{{ __('admin.package_create_intro') }}</p></div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="name_en" :value="__('admin.name_en')" />
                    <x-text-input id="name_en" name="name_en" class="mt-1 block w-full" :value="old('name_en')" required />
                    <x-input-error :messages="$errors->get('name_en')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="name_ar" :value="__('admin.name_ar')" />
                    <x-text-input id="name_ar" name="name_ar" class="mt-1 block w-full" :value="old('name_ar')" required />
                    <x-input-error :messages="$errors->get('name_ar')" class="mt-2" />
                </div>
            </div>

            <div>
                <x-input-label for="slug" :value="__('admin.slug')" />
                <x-text-input id="slug" name="slug" class="mt-1 block w-full" :value="old('slug')" dir="ltr" />
                <x-input-error :messages="$errors->get('slug')" class="mt-2" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="description_en" :value="__('admin.description_en')" />
                    <textarea id="description_en" name="description_en" rows="4"
                        class="mt-1 block w-full rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500">{{ old('description_en') }}</textarea>
                    <x-input-error :messages="$errors->get('description_en')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="description_ar" :value="__('admin.description_ar')" />
                    <textarea id="description_ar" name="description_ar" rows="4"
                        class="mt-1 block w-full rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500">{{ old('description_ar') }}</textarea>
                    <x-input-error :messages="$errors->get('description_ar')" class="mt-2" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <x-input-label for="cover_image" :value="__('admin.cover_image')" />
                    <input id="cover_image" type="file" name="cover_image" accept="image/*"
                        class="block w-full rounded-lg border border-slate-300 text-sm text-slate-500 file:mr-3 file:rounded-l-lg file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-700" />
                    <x-input-error :messages="$errors->get('cover_image')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="status" :value="__('admin.status')" />
                    <select id="status" name="status" class="mt-1 block w-full rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(old('status', \App\Enums\ProductStatus::Draft->value) === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                </div>
                <div class="flex h-full items-end pb-1">
                    <label class="flex cursor-pointer items-center gap-2 text-sm font-semibold text-slate-700">
                        <input type="checkbox" name="featured" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500" @checked(old('featured'))>
                        {{ __('store.featured_products') }}
                    </label>
                </div>
            </div>

            <div x-data="{ enabled: @js((bool) old('use_component_pricing', true)) }">
                <label class="flex cursor-pointer items-center gap-2 text-sm font-semibold text-slate-700">
                    <input type="checkbox" name="use_component_pricing" value="1" x-model="enabled"
                        class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    {{ __('admin.use_component_pricing') }}
                </label>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2" x-show="!enabled">
                    <div>
                        <x-input-label for="base_price" :value="__('admin.base_price')" />
                        <x-text-input id="base_price" type="number" step="0.01" min="0" name="base_price" class="mt-1 block w-full" :value="old('base_price')" />
                        <p class="mt-1 text-xs text-slate-500">{{ __('admin.price_includes_shipping') }}</p>
                        <x-input-error :messages="$errors->get('base_price')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div>
                <x-input-label for="discount_amount" :value="__('admin.discount_amount')" />
                <x-text-input id="discount_amount" type="number" step="0.01" min="0" name="discount_amount" class="mt-1 block w-full" :value="old('discount_amount', 0)" />
                <x-input-error :messages="$errors->get('discount_amount')" class="mt-2" />
            </div>
        </div>

        <div class="ui-panel rounded-3xl p-5 sm:p-7" x-data="packageEditor(@js(['products' => $productOptions, 'items' => array_values((array) old('items', [])), 'searchUrl' => route('admin.products.options'), 'activeOnly' => true, 'calculateUrl' => route('admin.packages.calculate'), 'csrf' => csrf_token(), 'maxItems' => \App\Services\PackageItemsValidator::MAX_ITEMS]))">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('admin.package_items') }}</h2>
                <span class="text-sm font-bold text-brand-700">{{ __('admin.package_total') }}: <span x-text="total" aria-live="polite">—</span></span>
            </div>

            <div class="mt-4">
                <x-admin.catalog-search />
                <p x-cloak x-show="calculationError" role="alert" class="mb-3 text-sm text-rose-700">{{ __('admin.catalog_error') }}</p>
                <template x-for="(item, index) in items" :key="index">
                    <div class="mb-3 grid grid-cols-1 gap-3 sm:grid-cols-[1.5fr_1fr_auto_auto]">
                        <select x-model="item.product_id" :name="`items[${index}][product_id]`" aria-label="{{ __('admin.select_product') }}"
                            class="rounded-lg border-slate-300 px-3 py-2 text-sm">
                            <option value="">— {{ __('admin.select_product') }} —</option>
                            <template x-for="product in products" :key="product.id">
                                <option :value="product.id" x-text="product.name + ' · ' + product.sku + ' (' + product.price + ')'"></option>
                            </template>
                        </select>
                        <input type="number" min="1" max="9999" aria-label="{{ __('store.quantity') }}" x-model.number="item.quantity" :name="`items[${index}][quantity]`"
                            class="rounded-lg border-slate-300 px-3 py-2 text-sm">
                        <span class="self-center text-sm font-semibold text-slate-500" x-text="lineTotals[index] ?? ''"></span>
                        <button type="button" x-on:click="remove(index)" class="rounded-lg border border-slate-200 px-3 py-2 text-rose-500 hover:bg-rose-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </template>
                <div x-show="!items.length" class="rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-400" x-text="'{{ __('admin.no_items') }}'"></div>
                <button type="button" x-on:click="add()" :disabled="items.length >= {{ \App\Services\PackageItemsValidator::MAX_ITEMS }}"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-brand-200 bg-brand-50 px-4 py-2 text-sm font-bold text-brand-700 hover:bg-brand-100">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    {{ __('admin.add_item') }}
                </button>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3">
            <a href="{{ route('admin.packages.index') }}" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-500 transition hover:bg-slate-100 hover:text-slate-800">{{ __('admin.cancel') }}</a>
            <button type="submit" class="ui-button-primary">{{ __('admin.create') }}</button>
        </div>
    </form>

</x-admin.layout>
