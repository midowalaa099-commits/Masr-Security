<x-store.layout>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('store.cart') }} @if ($items->isNotEmpty())(<span class="text-base font-medium text-slate-400">{{ $items->sum('quantity') }}</span>)@endif</h1>

        @if ($items->isEmpty())
            <div class="mt-8 flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white py-20 text-center">
                <svg class="h-16 w-16 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" /></svg>
                <p class="mt-4 text-sm font-semibold text-slate-700">{{ __('store.cart_empty') }}</p>
                <a href="{{ route('shop') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-brand-700 px-6 py-3 text-sm font-bold text-white transition hover:bg-brand-800">
                    {{ __('store.continue_shopping') }}
                </a>
            </div>
        @else
            <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-[1fr_360px]">
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <ul class="divide-y divide-slate-100">
                        @foreach ($items as $item)
                            <li class="flex gap-4 p-4 sm:p-5">
                                <a href="{{ $item->type === 'package' ? route('packages.show', $item->model) : route('products.show', $item->model) }}"
                                    class="block h-24 w-24 shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
                                    @if ($item->imageUrl)
                                        <img src="{{ $item->imageUrl }}" alt="{{ $item->name }}" class="h-full w-full object-cover">
                                    @else
                                        <div class="flex h-full w-full items-center justify-center text-slate-300">
                                            <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>
                                        </div>
                                    @endif
                                </a>

                                <div class="flex min-w-0 flex-1 flex-col">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            @if (! $item->isAvailable)
                                                <span class="inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-bold text-rose-700">{{ $item->stockLabel }}</span>
                                            @endif
                                            <a href="{{ $item->type === 'package' ? route('packages.show', $item->model) : route('products.show', $item->model) }}"
                                                class="mt-1 line-clamp-2 text-sm font-semibold text-slate-900 hover:text-brand-700">
                                                {{ $item->name }}
                                            </a>
                                            <p class="mt-0.5 text-xs text-slate-400">
                                                @if ($item->savingsPerUnit() > 0)
                                                    <span class="inline-block rounded bg-emerald-50 px-1.5 py-0.5 font-semibold text-emerald-600">{{ __('store.savings') }} {{ money($item->savingsPerUnit()) }} / pc</span>
                                                @else
                                                    {{ __('store.unit_price') }}
                                                @endif
                                            </p>
                                        </div>
                                        <div class="text-end">
                                            <p class="text-base font-bold text-slate-900">{{ money($item->lineTotal()) }}</p>
                                            <p class="text-xs text-slate-400">{{ money($item->unitPrice) }} × {{ $item->quantity }}</p>
                                        </div>
                                    </div>

                                    <div class="mt-auto flex items-center justify-between gap-3 pt-3">
                                        <form method="POST" action="{{ route('cart.update', [$item->type, $item->id]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <div class="inline-flex items-center rounded-lg border border-slate-300 bg-white">
                                                <button type="submit" name="quantity" value="{{ max(1, $item->quantity - 1) }}" class="px-2.5 py-1.5 text-slate-500 hover:text-slate-900" aria-label="−">−</button>
                                                <span class="w-12 text-center text-sm font-semibold text-slate-900">{{ $item->quantity }}</span>
                                                <button type="submit" name="quantity" value="{{ min($item->availableQuantity, $item->quantity + 1) }}" class="px-2.5 py-1.5 text-slate-500 hover:text-slate-900" aria-label="+" {{ $item->quantity >= $item->availableQuantity ? 'disabled' : '' }}>+</button>
                                            </div>
                                        </form>

                                        <form method="POST" action="{{ route('cart.remove', [$item->type, $item->id]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-400 hover:text-rose-600">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                                {{ __('store.remove') }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="flex items-center justify-between border-t border-slate-100 px-4 py-3">
                        <form method="POST" action="{{ route('cart.clear') }}">
                            @csrf
                            <button type="submit" class="text-xs font-semibold text-slate-400 hover:text-rose-600">{{ __('store.clear_cart') }}</button>
                        </form>
                        <a href="{{ route('shop') }}" class="text-xs font-semibold text-brand-700 hover:text-brand-800">{{ __('store.continue_shopping') }}</a>
                    </div>
                </div>

                <!-- Summary -->
                <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:sticky lg:top-28">
                    <h2 class="text-lg font-bold text-slate-900">{{ __('store.cart') }}</h2>

                    <dl class="mt-5 space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <dt class="text-slate-500">{{ __('store.subtotal') }}</dt>
                            <dd class="font-semibold text-slate-900">{{ money($subtotal) }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-slate-500">{{ __('store.shipping') }}</dt>
                            <dd class="font-semibold text-emerald-600">{{ __('store.calculated_at_checkout') }}</dd>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                            <dt class="text-base font-bold text-slate-900">{{ __('store.total') }}</dt>
                            <dd class="text-xl font-extrabold text-brand-800">{{ money($subtotal) }}</dd>
                        </div>
                    </dl>

                    @if ($items->contains(fn ($item) => ! $item->isAvailable))
                        <p class="mt-4 rounded-xl bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700">
                            {{ __('store.some_items_unavailable') }}
                        </p>
                    @endif

                    <a href="{{ route('checkout.index') }}"
                        class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl bg-brand-700 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-brand-900/20 transition hover:bg-brand-800">
                        {{ __('store.checkout_now') }}
                        <svg class="h-4 w-4 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                    </a>
                </aside>
            </div>
        @endif
    </div>

</x-store.layout>