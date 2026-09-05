@props(['package', 'showAddToCart' => true])

@php
    $href = route('packages.show', $package);
@endphp

<div class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
    <a href="{{ $href }}" class="relative block aspect-[4/3] overflow-hidden bg-slate-100">
        @if ($package->firstImageUrl())
            <img src="{{ $package->firstImageUrl() }}" alt="{{ $package->trans('name') }}" loading="lazy"
                class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
        @else
            <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-brand-700 to-navy-950">
                <svg class="h-14 w-14 text-white/60" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M3.75 5.25a75 75 0 0016.5 0m-16.5 0A11.985 11.985 0 013.75 12c0 .923.076 1.826.22 2.708M20.25 5.25A11.985 11.985 0 0120.25 12c0 5.592-3.824 10.29-9 11.622-5.176-1.332-9-6.03-9-11.622 0-1.31.21-2.571.598-3.751" /></svg>
            </div>
        @endif

        @if ($package->items->isNotEmpty())
            <span class="absolute start-3 top-3 inline-flex items-center gap-1 rounded-full bg-white/95 px-2.5 py-1 text-[11px] font-bold text-slate-700 shadow">
                <svg class="h-3 w-3 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                {{ $package->items_count ?? $package->items->count() }} {{ __('admin.component_count') }}
            </span>
        @endif

        @if ($package->isOutOfStock())
            <div class="absolute inset-0 flex items-center justify-center bg-white/70 backdrop-blur-[2px]">
                <span class="rounded-full bg-slate-900 px-4 py-1.5 text-xs font-bold uppercase tracking-wide text-white">
                    {{ __('store.out_of_stock') }}
                </span>
            </div>
        @endif
    </a>

    <div class="flex flex-1 flex-col p-4">
        <a href="{{ $href }}" class="line-clamp-2 text-sm font-semibold text-slate-900 hover:text-brand-700">
            {{ $package->trans('name') }}
        </a>

        <div class="mt-2">
            @if ($package->useComponentPricing())
                <span class="text-[11px] font-medium text-emerald-600">{{ __('store.pkg_savings') }}: {{ money($package->discount_amount) }}</span>
            @endif
        </div>

        <div class="mt-auto flex items-end justify-between gap-2 pt-3">
            <div class="flex flex-col">
                <span class="text-lg font-bold text-brand-800">{{ money($package->displayPrice()) }}</span>
            </div>

            @if ($showAddToCart && ! $package->isOutOfStock())
                <form method="POST" action="{{ route('cart.add') }}">
                    @csrf
                    <input type="hidden" name="type" value="package">
                    <input type="hidden" name="cartable" value="{{ $package->id }}">
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