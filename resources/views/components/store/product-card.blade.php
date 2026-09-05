@props(['product', 'showAddToCart' => true])

@php
    $href = route('products.show', $product);
@endphp

<div class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
    <a href="{{ $href }}" class="relative block aspect-[4/3] overflow-hidden bg-slate-100">
        @if ($product->firstImageUrl())
            <img src="{{ $product->firstImageUrl() }}" alt="{{ $product->trans('name') }}" loading="lazy"
                class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
        @else
            <div class="flex h-full w-full items-center justify-center bg-slate-100 text-slate-300">
                <svg class="h-12 w-12" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>
            </div>
        @endif

        <div class="absolute start-3 top-3 flex flex-col items-start gap-1.5">
            @if ($product->isOnSale())
                <span class="inline-flex items-center rounded-full bg-rose-600 px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wide text-white shadow">
                    Sale
                </span>
            @endif
            @if ($product->isLowStock())
                <span class="inline-flex items-center rounded-full bg-amber-500 px-2.5 py-0.5 text-[11px] font-bold text-white shadow">
                    {{ __('store.low_stock') }}
                </span>
            @endif
        </div>

        @if ($product->isOutOfStock())
            <div class="absolute inset-0 flex items-center justify-center bg-white/70 backdrop-blur-[2px]">
                <span class="rounded-full bg-slate-900 px-4 py-1.5 text-xs font-bold uppercase tracking-wide text-white">
                    {{ __('store.out_of_stock') }}
                </span>
            </div>
        @endif
    </a>

    <div class="flex flex-1 flex-col p-4">
        @if ($product->category)
            <span class="text-[11px] font-semibold uppercase tracking-wider text-brand-600">{{ $product->category->trans('name') }}</span>
        @endif
        <a href="{{ $href }}" class="mt-1 line-clamp-2 text-sm font-semibold text-slate-900 hover:text-brand-700">
            {{ $product->trans('name') }}
        </a>

        <div class="mt-auto flex items-end justify-between gap-2 pt-3">
            <div class="flex flex-col">
                @if ($product->isOnSale())
                    <span class="text-xs line-through text-slate-400">{{ money($product->originalPrice()) }}</span>
                @endif
                <span class="text-lg font-bold text-brand-800">{{ money($product->displayPrice()) }}</span>
            </div>

            @if ($showAddToCart && ! $product->isOutOfStock())
                <form method="POST" action="{{ route('cart.add') }}">
                    @csrf
                    <input type="hidden" name="type" value="product">
                    <input type="hidden" name="cartable" value="{{ $product->id }}">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-700 px-3 py-2 text-sm font-semibold text-white transition hover:bg-brand-800"
                            aria-label="{{ __('store.add_to_cart') }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        <span class="hidden sm:inline">{{ __('store.add_to_cart') }}</span>
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>