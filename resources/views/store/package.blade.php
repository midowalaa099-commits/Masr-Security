<x-store.layout>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        <nav class="text-xs text-slate-400">
            <a href="{{ route('home') }}" class="hover:text-brand-600">{{ __('store.home') }}</a>
            <span class="mx-1.5">/</span>
            <a href="{{ route('packages.index') }}" class="hover:text-brand-600">{{ __('store.featured_packages') }}</a>
        </nav>

        <div class="mt-6 grid grid-cols-1 gap-10 lg:grid-cols-2">
            <!-- Cover -->
            <div>
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-brand-700 to-navy-950 shadow-lg">
                    @if ($package->firstImageUrl())
                        <img src="{{ $package->firstImageUrl() }}" alt="{{ $package->trans('name') }}" class="aspect-[4/3] w-full object-cover">
                    @else
                        <div class="flex aspect-[4/3] w-full items-center justify-center">
                            <svg class="h-24 w-24 text-white/50" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M3.75 5.25a75 75 0 0016.5 0m-16.5 0A11.985 11.985 0 013.75 12c0 .923.076 1.826.22 2.708M20.25 5.25A11.985 11.985 0 0120.25 12c0 5.592-3.824 10.29-9 11.622-5.176-1.332-9-6.03-9-11.622 0-1.31.21-2.571.598-3.751" /></svg>
                        </div>
                    @endif
                </div>

                @if ($package->trans('description'))
                    <div class="prose-sm mt-6 max-w-none text-slate-600">
                        <h2 class="text-lg font-bold text-slate-900">{{ __('store.description') }}</h2>
                        <p class="mt-2 leading-relaxed whitespace-pre-line">{{ $package->trans('description') }}</p>
                    </div>
                @endif
            </div>

            <!-- Summary -->
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-brand-600">{{ __('store.featured_packages') }}</span>
                    @if ($package->isOutOfStock())
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-500">{{ __('store.out_of_stock') }}</span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700">{{ __('store.in_stock') }}</span>
                    @endif
                </div>

                <h1 class="mt-2 text-2xl font-bold text-slate-900 sm:text-3xl">{{ $package->trans('name') }}</h1>

                <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-baseline justify-between gap-4">
                        <div>
                            <span class="text-3xl font-extrabold text-brand-800">{{ money($package->displayPrice()) }}</span>
                            @if ($package->useComponentPricing())
                                <span class="ms-2 text-sm text-slate-400 line-through">{{ money($package->componentsTotal()) }}</span>
                            @endif
                        </div>
                        @if ($package->useComponentPricing() && $package->discount_amount > 0)
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">
                                {{ __('store.savings') }} {{ money($package->discount_amount) }}
                            </span>
                        @endif
                    </div>

                    @if ($package->isAvailable())
                        <form method="POST" action="{{ route('cart.add') }}" x-data="{ qty: 1 }" class="mt-5">
                            @csrf
                            <input type="hidden" name="type" value="package">
                            <input type="hidden" name="cartable" value="{{ $package->id }}">

                            <div class="flex flex-wrap items-center gap-4">
                                <div class="inline-flex items-center rounded-xl border border-slate-300 bg-white">
                                    <button type="button" @click="qty = Math.max(1, qty - 1)" class="px-3 py-2.5 text-slate-500 hover:text-slate-900">−</button>
                                    <input type="number" x-model.number="qty" name="quantity" min="1" max="{{ max(1, $package->availableQuantity()) }}"
                                        class="w-16 border-x border-slate-200 py-2 text-center text-sm font-semibold text-slate-900">
                                    <button type="button" @click="qty = Math.min({{ max(1, $package->availableQuantity()) }}, qty + 1)" class="px-3 py-2.5 text-slate-500 hover:text-slate-900">+</button>
                                </div>

                                <button type="submit" class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-brand-700 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-brand-900/20 transition hover:bg-brand-800 sm:flex-none">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z" /></svg>
                                    {{ __('store.add_to_cart') }}
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                            {{ __('store.out_of_stock') }} — <a href="{{ route('contact') }}" class="font-semibold text-brand-700 hover:text-brand-800">{{ __('store.contact') }}</a>
                        </div>
                    @endif
                </div>

                <!-- Components -->
                @if ($package->items->isNotEmpty())
                    <div class="mt-8">
                        <h2 class="text-lg font-bold text-slate-900">{{ __('store.included_components') }}</h2>
                        <ul class="mt-4 divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                            @foreach ($package->items as $item)
                                <li class="flex items-center gap-4 px-4 py-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 font-bold text-brand-700">
                                        {{ $item->quantity }}×
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-slate-800">
                                            @if ($item->product && ! $item->product->isOutOfStock())
                                                <a href="{{ route('products.show', $item->product) }}" class="hover:text-brand-700">{{ $item->product->trans('name') }}</a>
                                            @else
                                                {{ $item->product?->trans('name') ?? '—' }}
                                            @endif
                                        </p>
                                        @if ($item->product)
                                            <p class="text-xs text-slate-400">{{ $item->product->sku }}</p>
                                        @endif
                                    </div>
                                    @if ($item->product)
                                        <span class="text-sm font-semibold text-slate-600">{{ money($item->product->displayPrice() * $item->quantity) }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>

</x-store.layout>