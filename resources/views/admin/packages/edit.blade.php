<x-admin.layout title="{{ __('admin.packages') }}">

    @php
        $statuses = \App\Enums\ProductStatus::cases();
        $productOptions = $products->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name_en,
            'price' => $p->displayPrice(),
        ]);
        $existingItems = $package->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
        ]);
    @endphp

    <form method="POST" action="{{ route('admin.packages.update', $package) }}" enctype="multipart/form-data"
        class="space-y-6">
        @csrf
        @method('PUT')

        <div class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('admin.package_info') }}</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="name_en" :value="__('admin.name_en')" />
                    <x-text-input id="name_en" name="name_en" class="mt-1 block w-full" :value="old('name_en', $package->name_en)" required />
                    <x-input-error :messages="$errors->get('name_en')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="name_ar" :value="__('admin.name_ar')" />
                    <x-text-input id="name_ar" name="name_ar" class="mt-1 block w-full" :value="old('name_ar', $package->name_ar)" required />
                    <x-input-error :messages="$errors->get('name_ar')" class="mt-2" />
                </div>
            </div>

            <div>
                <x-input-label for="slug" :value="__('admin.slug')" />
                <x-text-input id="slug" name="slug" class="mt-1 block w-full" :value="old('slug', $package->slug)" dir="ltr" />
                <x-input-error :messages="$errors->get('slug')" class="mt-2" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="description_en" :value="__('admin.description_en')" />
                    <textarea id="description_en" name="description_en" rows="4"
                        class="mt-1 block w-full rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500">{{ old('description_en', $package->description_en) }}</textarea>
                    <x-input-error :messages="$errors->get('description_en')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="description_ar" :value="__('admin.description_ar')" />
                    <textarea id="description_ar" name="description_ar" rows="4"
                        class="mt-1 block w-full rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500">{{ old('description_ar', $package->description_ar) }}</textarea>
                    <x-input-error :messages="$errors->get('description_ar')" class="mt-2" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <x-input-label for="cover_image" :value="__('admin.cover_image')" />
                    <div class="mt-1 flex items-center gap-4">
                        @if ($package->cover_image)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($package->cover_image) }}" alt="{{ $package->trans('name') }}" class="h-16 w-16 rounded-xl border border-slate-200 object-cover">
                        @endif
                        <input id="cover_image" type="file" name="cover_image" accept="image/*"
                            class="block w-full rounded-lg border border-slate-300 text-sm text-slate-500 file:mr-3 file:rounded-l-lg file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-700" />
                    </div>
                    <x-input-error :messages="$errors->get('cover_image')" class="mt-2" />
                    @if ($package->cover_image)
                        <label class="mt-2 flex cursor-pointer items-center gap-2 text-sm text-slate-500">
                            <input type="checkbox" name="remove_cover" value="1" class="h-4 w-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                            {{ __('admin.remove_image') }}
                        </label>
                    @endif
                </div>
                <div>
                    <x-input-label for="status" :value="__('admin.status')" />
                    <select id="status" name="status" class="mt-1 block w-full rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(old('status', $package->status->value) === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                </div>
                <div class="flex h-full items-end pb-1">
                    <label class="flex cursor-pointer items-center gap-2 text-sm font-semibold text-slate-700">
                        <input type="checkbox" name="featured" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500" @checked(old('featured', $package->featured))>
                        {{ __('store.featured_products') }}
                    </label>
                </div>
            </div>

            <div x-data="{ enabled: @js(old('use_component_pricing', $package->use_component_pricing)) }">
                <label class="flex cursor-pointer items-center gap-2 text-sm font-semibold text-slate-700">
                    <input type="checkbox" name="use_component_pricing" value="1" x-model="enabled"
                        class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    {{ __('admin.use_component_pricing') }}
                </label>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2" x-show="!enabled">
                    <div>
                        <x-input-label for="base_price" :value="__('admin.base_price')" />
                        <x-text-input id="base_price" type="number" step="0.01" min="0" name="base_price" class="mt-1 block w-full" :value="old('base_price', $package->base_price)" />
                        <x-input-error :messages="$errors->get('base_price')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div>
                <x-input-label for="discount_amount" :value="__('admin.discount_amount')" />
                <x-text-input id="discount_amount" type="number" step="0.01" min="0" name="discount_amount" class="mt-1 block w-full" :value="old('discount_amount', $package->discount_amount)" />
                <x-input-error :messages="$errors->get('discount_amount')" class="mt-2" />
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('admin.package_items') }}</h2>
                <span class="text-sm font-bold text-brand-700">{{ __('admin.package_total') }}: <span id="package-total">—</span></span>
            </div>

            <div x-data="{
                products: @json($productOptions),
                items: @json($existingItems->all()),
                add() {
                    if (! this.products.length) return;
                    this.items.push({ product_id: '', quantity: 1 });
                },
                remove(index) { this.items.splice(index, 1); },
                productPrice(id) { return Number(this.products.find((p) => String(p.id) === String(id))?.price ?? 0); },
                async recalculate() {
                    const payload = this.items
                        .filter((row) => row.product_id)
                        .map((row) => ({ product_id: Number(row.product_id), quantity: Number(row.quantity) || 1 }));
                    if (! payload.length) { document.getElementById('package-total').textContent = '—'; return; }
                    try {
                        const res = await fetch('{{ route('admin.packages.calculate') }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                            body: JSON.stringify({ items: payload }),
                        });
                        const data = await res.json();
                        if (res.ok && data.formatted) document.getElementById('package-total').textContent = data.formatted;
                    } catch (e) {}
                },
                init() {
                    this.$watch('items', () => this.recalculate());
                    this.recalculate();
                },
            }" class="mt-4">
                <template x-for="(item, index) in items" :key="index">
                    <div class="mb-3 grid grid-cols-1 gap-3 sm:grid-cols-[1.5fr_1fr_auto_auto]">
                        <select x-model="item.product_id" :name="`items[${index}][product_id]`" x-on:change="recalculate()"
                            class="rounded-lg border-slate-300 px-3 py-2 text-sm">
                            <option value="">— {{ __('admin.select_product') }} —</option>
                            <template x-for="product in products" :key="product.id">
                                <option :value="product.id" x-text="product.name + ' (' + product.price + ')'"></option>
                            </template>
                        </select>
                        <input type="number" min="1" x-model.number="item.quantity" :name="`items[${index}][quantity]`" x-on:input="recalculate()"
                            class="rounded-lg border-slate-300 px-3 py-2 text-sm">
                        <span class="self-center text-sm font-semibold text-slate-500" x-html="item.product_id ? (productPrice(item.product_id) * (item.quantity || 1)).toFixed(2) + ' ' + '{{ __('store.currency_egp') }}' : ''"></span>
                        <button type="button" x-on:click="remove(index)" class="rounded-lg border border-slate-200 px-3 py-2 text-rose-500 hover:bg-rose-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </template>
                <div x-show="!items.length" class="rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-400">{{ __('admin.no_items') }}</div>
                <button type="button" x-on:click="add()"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-brand-200 bg-brand-50 px-4 py-2 text-sm font-bold text-brand-700 hover:bg-brand-100">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    {{ __('admin.add_item') }}
                </button>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-brand-700 px-6 py-2.5 text-sm font-bold text-white hover:bg-brand-800">{{ __('admin.save') }}</button>
            <a href="{{ route('admin.packages.index') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-700">{{ __('admin.cancel') }}</a>
        </div>
    </form>

</x-admin.layout>