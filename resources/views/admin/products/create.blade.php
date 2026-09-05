<x-admin.layout title="{{ __('admin.products') }}">

    @php
        $statuses = \App\Enums\ProductStatus::cases();
        $types = \App\Enums\ProductType::cases();
    @endphp

    <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data"
        class="space-y-6">
        @csrf

        <div class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('admin.product_info') }}</h2>

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

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <x-input-label for="sku" :value="__('admin.sku')" />
                    <x-text-input id="sku" name="sku" class="mt-1 block w-full" :value="old('sku')" dir="ltr" placeholder="HIK-DS-2CD" required />
                    <x-input-error :messages="$errors->get('sku')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="slug" :value="__('admin.slug')" />
                    <x-text-input id="slug" name="slug" class="mt-1 block w-full" :value="old('slug')" dir="ltr" />
                    <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="category_id" :value="__('admin.categories')" />
                    <select id="category_id" name="category_id" class="mt-1 block w-full rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500">
                        <option value="">— {{ __('admin.none') }} —</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name_en }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="brand" :value="__('admin.brand')" />
                    <x-text-input id="brand" name="brand" class="mt-1 block w-full" :value="old('brand')" />
                    <x-input-error :messages="$errors->get('brand')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="model_number" :value="__('admin.model_number')" />
                    <x-text-input id="model_number" name="model_number" class="mt-1 block w-full" :value="old('model_number')" />
                    <x-input-error :messages="$errors->get('model_number')" class="mt-2" />
                </div>
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
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('admin.pricing') }}</h2>

                <div>
                    <x-input-label for="price" :value="__('admin.price')" />
                    <x-text-input id="price" type="number" step="0.01" min="0.01" name="price" class="mt-1 block w-full" :value="old('price')" required />
                    <x-input-error :messages="$errors->get('price')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="sale_price" :value="__('admin.sale_price')" />
                    <x-text-input id="sale_price" type="number" step="0.01" min="0" name="sale_price" class="mt-1 block w-full" :value="old('sale_price')" />
                    <x-input-error :messages="$errors->get('sale_price')" class="mt-2" />
                </div>

                <h2 class="pt-2 text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('admin.inventory') }}</h2>

                <div>
                    <x-input-label for="stock_quantity" :value="__('admin.stock')" />
                    <x-text-input id="stock_quantity" type="number" min="0" name="stock_quantity" class="mt-1 block w-full" :value="old('stock_quantity', 0)" required />
                    <x-input-error :messages="$errors->get('stock_quantity')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="low_stock_threshold" :value="__('admin.low_stock_threshold')" />
                    <x-text-input id="low_stock_threshold" type="number" min="0" name="low_stock_threshold" class="mt-1 block w-full" :value="old('low_stock_threshold', 5)" required />
                    <x-input-error :messages="$errors->get('low_stock_threshold')" class="mt-2" />
                </div>

                <h2 class="pt-2 text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('admin.status') }}</h2>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="status" :value="__('admin.status')" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500">
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected(old('status', \App\Enums\ProductStatus::Draft->value) === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="type" :value="__('admin.type')" />
                        <select id="type" name="type" class="mt-1 block w-full rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500">
                            @foreach ($types as $type)
                                <option value="{{ $type->value }}" @selected(old('type', \App\Enums\ProductType::Simple->value) === $type->value)>{{ $type->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('type')" class="mt-2" />
                    </div>
                </div>

                <label class="flex cursor-pointer items-center gap-2 text-sm font-semibold text-slate-700">
                    <input type="checkbox" name="featured" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500" @checked(old('featured'))>
                    {{ __('store.featured_products') }}
                </label>
            </div>

            <div class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('admin.images') }}</h2>

                <input type="file" name="images[]" multiple accept="image/*"
                    class="block w-full rounded-lg border border-slate-300 text-sm text-slate-500 file:mr-3 file:rounded-l-lg file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-700" />
                <p class="text-xs text-slate-400">{{ __('admin.max_images') }}</p>
                <x-input-error :messages="$errors->get('images')" class="mt-2" />

                <h2 class="pt-2 text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('admin.specs') }}</h2>

                <div x-data="{ specs: [] }">
                    <template x-for="(spec, index) in specs" :key="index">
                        <div class="mb-3 grid grid-cols-1 gap-3 sm:grid-cols-[1fr_1fr_1fr_auto]">
                            <input type="text" x-model="spec.key" :name="`specs[${index}][key]`" placeholder="{{ __('admin.spec_key') }}"
                                class="rounded-lg border-slate-300 px-3 py-2 text-sm" />
                            <input type="text" x-model="spec.value_en" :name="`specs[${index}][value_en]`" placeholder="{{ __('admin.spec_value_en') }}"
                                class="rounded-lg border-slate-300 px-3 py-2 text-sm" />
                            <input type="text" x-model="spec.value_ar" :name="`specs[${index}][value_ar]`" placeholder="{{ __('admin.spec_value_ar') }}"
                                class="rounded-lg border-slate-300 px-3 py-2 text-sm" />
                            <button type="button" x-on:click="specs.splice(index, 1)" class="rounded-lg border border-slate-200 px-3 py-2 text-rose-500 hover:bg-rose-50">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </template>
                    <button type="button" x-on:click="specs.push({ key: '', value_en: '', value_ar: '' })"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-brand-200 bg-brand-50 px-4 py-2 text-sm font-bold text-brand-700 hover:bg-brand-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        {{ __('admin.add_spec') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-brand-700 px-6 py-2.5 text-sm font-bold text-white hover:bg-brand-800">{{ __('admin.create') }}</button>
            <a href="{{ route('admin.products.index') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-700">{{ __('admin.cancel') }}</a>
        </div>
    </form>

</x-admin.layout>