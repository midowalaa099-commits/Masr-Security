<x-store.layout>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        <nav class="text-xs text-slate-400">
            <a href="{{ route('home') }}" class="hover:text-brand-600">{{ __('store.home') }}</a>
            <span class="mx-1.5">/</span>
            <a href="{{ route('shop') }}" class="hover:text-brand-600">{{ __('store.shop') }}</a>
            @if ($product->category)
                <span class="mx-1.5">/</span>
                <a href="{{ route('categories.show', $product->category) }}" class="hover:text-brand-600">{{ $product->category->trans('name') }}</a>
            @endif
        </nav>

        <div class="mt-6 grid grid-cols-1 gap-10 lg:grid-cols-2">
            <!-- Gallery -->
            <div x-data="{ active: 0 }">
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    @if ($product->images->isNotEmpty())
                        <img x-bind:src="[{{ $product->images->map(fn ($i) => "'".$i->url."'")->implode(',') }}][active]" alt="{{ $product->trans('name') }}"
                            class="aspect-[4/3] w-full object-cover">
                    @else
                        <div class="flex aspect-[4/3] w-full items-center justify-center bg-slate-100 text-slate-300">
                            <svg class="h-20 w-20" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>
                        </div>
                    @endif
                </div>

                @if ($product->images->count() > 1)
                    <div class="mt-4 grid grid-cols-5 gap-3">
                        @foreach ($product->images as $index => $image)
                            <img :src="'{{ $image->url }}'" @click="active = {{ $index }}" alt="{{ $product->trans('name') }}"
                                :class="active === {{ $index }} ? 'border-brand-600' : 'border-transparent hover:border-slate-300'"
                                class="cursor-pointer rounded-lg border-2 object-cover transition aspect-[4/3]">
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Summary -->
            <div>
                <div class="flex items-center gap-2">
                    @if ($product->category)
                        <span class="text-xs font-bold uppercase tracking-wider text-brand-600">{{ $product->category->trans('name') }}</span>
                    @endif
                    @if ($product->isOnSale())
                        <span class="inline-flex items-center rounded-full bg-rose-600 px-2.5 py-0.5 text-[11px] font-bold text-white">Sale</span>
                    @endif
                </div>

                <h1 class="mt-2 text-2xl font-bold text-slate-900 sm:text-3xl">{{ $product->trans('name') }}</h1>

                @if ($product->sku)
                    <p class="mt-1 text-sm text-slate-400">{{ __('store.sku') }}: <span class="font-mono" dir="ltr">{{ $product->sku }}</span></p>
                @endif

                <div class="mt-4 flex items-baseline gap-3">
                    <span class="text-3xl font-extrabold text-brand-800">{{ money($product->displayPrice()) }}</span>
                    @if ($product->isOnSale())
                        <span class="text-base text-slate-400 line-through">{{ money($product->originalPrice()) }}</span>
                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">{{ __('store.savings') }} {{ money($product->originalPrice() - $product->displayPrice()) }}</span>
                    @endif
                </div>

                <div class="mt-3">
                    @if ($product->isOutOfStock())
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">
                            <span class="h-2 w-2 rounded-full bg-slate-400"></span>
                            {{ __('store.out_of_stock') }}
                        </span>
                    @elseif ($product->stockLabel() === __('store.low_stock'))
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700">
                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                            {{ __('store.low_stock') }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            {{ __('store.in_stock') }}
                        </span>
                    @endif
                </div>

                @if ($product->trans('description'))
                    <p class="mt-5 leading-relaxed whitespace-pre-line text-slate-600">{{ $product->trans('description') }}</p>
                @endif

                @if ($product->isAvailable())
                    <form method="POST" action="{{ route('cart.add') }}" x-data="{ qty: 1 }" class="mt-7">
                        @csrf
                        <input type="hidden" name="type" value="product">
                        <input type="hidden" name="cartable" value="{{ $product->id }}">

                        <div class="flex flex-wrap items-center gap-4">
                            <div class="inline-flex items-center rounded-xl border border-slate-300 bg-white">
                                <button type="button" @click="qty = Math.max(1, qty - 1)" class="px-3 py-2.5 text-slate-500 hover:text-slate-900">−</button>
                                <input type="number" x-model.number="qty" name="quantity" min="1" max="{{ max(1, $product->availableQuantity()) }}"
                                    class="w-16 border-x border-slate-200 py-2 text-center text-sm font-semibold text-slate-900">
                                <button type="button" @click="qty = Math.min({{ max(1, $product->availableQuantity()) }}, qty + 1)" class="px-3 py-2.5 text-slate-500 hover:text-slate-900">+</button>
                            </div>

                            <button type="submit" class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-brand-700 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-brand-900/20 transition hover:bg-brand-800 sm:flex-none">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z" /></svg>
                                {{ __('store.add_to_cart') }}
                            </button>
                        </div>
                    </form>
                @else
                    <div class="mt-7 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                        {{ __('store.out_of_stock') }} — <a href="{{ route('contact') }}" class="font-semibold text-brand-700 hover:text-brand-800">{{ __('store.contact') }}</a>
                    </div>
                @endif

                <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                        <svg class="mx-auto h-6 w-6 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                        <p class="mt-2 text-xs font-semibold text-slate-700">Original products</p>
                    </div>
                    @if ($product->warranty_months)
                    <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                        <svg class="mx-auto h-6 w-6 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <p class="mt-2 text-xs font-semibold text-slate-700">{{ $product->warranty_months }} months warranty</p>
                    </div>
                    @endif
                    <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                        <svg class="mx-auto h-6 w-6 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg>
                        <p class="mt-2 text-xs font-semibold text-slate-700">Delivery</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Specifications -->
        @if ($product->specs->isNotEmpty())
            <section class="mt-14">
                <h2 class="text-xl font-bold text-slate-900">{{ __('store.specifications') }}</h2>
                <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <table class="w-full text-sm">
                        <tbody>
                            @foreach ($product->specs as $spec)
                                <tr class="{{ $loop->even ? 'bg-slate-50' : '' }}">
                                    <th class="w-1/3 px-5 py-3 text-start font-semibold text-slate-700">{{ $spec->spec_key }}</th>
                                    <td class="px-5 py-3 text-slate-600">{{ $spec->value }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <!-- Related -->
        @if ($related->isNotEmpty())
            <section class="mt-14">
                <h2 class="text-xl font-bold text-slate-900">{{ __('store.related_products') }}</h2>
                <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($related as $relatedProduct)
                        <x-store.product-card :product="$relatedProduct" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>

</x-store.layout>