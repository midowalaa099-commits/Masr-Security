<x-store.layout>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('store.checkout') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('store.guest_note') }}</p>

        <form method="POST" action="{{ route('checkout.store') }}" x-data="{ method: '{{ old('payment_method', 'card') }}' }" class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-[1fr_400px]">
            @csrf

            <div class="space-y-6">
                <!-- Contact -->
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">1</span>
                        {{ __('store.contact') }}
                    </h2>

                    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="customer_name" :value="__('store.customer_name')" />
                            <x-text-input id="customer_name" name="customer_name" class="mt-1 block w-full" :value="old('customer_name', $preset['name'])"
                                placeholder="{{ __('store.customer_name') }}" required autocomplete="name" />
                            <x-input-error :messages="$errors->get('customer_name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="phone" :value="__('store.phone')" />
                            <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone', $preset['phone'])"
                                placeholder="01XXXXXXXXX" dir="ltr" required autocomplete="tel" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="email" :value="__('store.email')" />
                            <x-text-input id="email" type="email" name="email" class="mt-1 block w-full" :value="old('email', $preset['email'])"
                                dir="ltr" autocomplete="email" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                    </div>
                </section>

                <!-- Delivery -->
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">2</span>
                        {{ __('store.delivery_to') }}
                    </h2>

                    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="governorate" :value="__('store.governorate')" />
                            <x-text-input id="governorate" name="governorate" class="mt-1 block w-full" :value="old('governorate')"
                                placeholder="{{ __('store.governorate') }}" />
                            <x-input-error :messages="$errors->get('governorate')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="city" :value="__('store.city')" />
                            <x-text-input id="city" name="city" class="mt-1 block w-full" :value="old('city')" placeholder="{{ __('store.city') }}" />
                            <x-input-error :messages="$errors->get('city')" class="mt-2" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="address_line" :value="__('store.address_line')" />
                            <x-text-input id="address_line" name="address_line" class="mt-1 block w-full" :value="old('address_line')"
                                placeholder="{{ __('store.address_line') }}" />
                            <x-input-error :messages="$errors->get('address_line')" class="mt-2" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="notes" :value="__('store.notes')" />
                            <textarea id="notes" name="notes" rows="2"
                                class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                placeholder="{{ __('store.notes') }}">{{ old('notes') }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>
                    </div>
                </section>

                <!-- Payment -->
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">3</span>
                        {{ __('store.payment_method') }}
                    </h2>

                    <div class="mt-5 space-y-3">
                        @php
                            $options = [
                                'card' => ['label' => __('payments.method_card'), 'icon' => 'card', 'hint' => 'Visa · Mastercard'],
                                'wallet' => ['label' => __('payments.method_wallet'), 'icon' => 'wallet', 'hint' => 'Vodafone Cash'],
                            ];
                        @endphp

                        @foreach ($paymentMethods as $paymentMethod)
                            @php $option = $options[$paymentMethod->value] ?? null; @endphp
                            @continue(! $option)

                            <label class="flex cursor-pointer items-center gap-4 rounded-xl border-2 p-4 transition {{ $paymentMethod->value === old('payment_method', 'card') ? 'border-brand-600 bg-brand-50/50' : 'border-slate-200 hover:border-slate-300' }}"
                                :class="method === '{{ $paymentMethod->value }}' ? 'border-brand-600 bg-brand-50/50' : 'border-slate-200'">
                                <input type="radio" name="payment_method" value="{{ $paymentMethod->value }}" x-model="method"
                                    class="h-4 w-4 border-slate-300 text-brand-600 focus:ring-brand-500">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-brand-700">
                                    @if ($option['icon'] === 'wallet')
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3" /></svg>
                                    @else
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    @endif
                                </span>
                                <span class="flex-1">
                                    <span class="block text-sm font-bold text-slate-900">{{ $option['label'] }}</span>
                                    <span class="block text-xs text-slate-400">{{ $option['hint'] }}</span>
                                </span>
                            </label>
                        @endforeach
                        <x-input-error :messages="$errors->get('payment_method')" class="mt-2" />
                    </div>
                </section>
            </div>

            <!-- Summary -->
            <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:sticky lg:top-28">
                <h2 class="text-base font-bold text-slate-900">{{ __('store.cart') }} ({{ $items->count() }})</h2>

                <ul class="mt-4 max-h-72 space-y-3 overflow-y-auto pe-1">
                    @foreach ($items as $item)
                        <li class="flex items-center gap-3">
                            <span class="block h-12 w-12 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                                @if ($item->imageUrl)
                                    <img src="{{ $item->imageUrl }}" alt="{{ $item->name }}" class="h-full w-full object-cover">
                                @endif
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="line-clamp-1 text-xs font-semibold text-slate-800">{{ $item->name }}</p>
                                <p class="text-[11px] text-slate-400">{{ $item->quantity }} × {{ money($item->unitPrice) }}</p>
                            </div>
                            <span class="text-sm font-semibold text-slate-900">{{ money($item->lineTotal()) }}</span>
                        </li>
                    @endforeach
                </ul>

                <dl class="mt-5 space-y-3 border-t border-slate-100 pt-5 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">{{ __('store.subtotal') }}</dt>
                        <dd class="font-semibold text-slate-900">{{ money($subtotal) }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">{{ __('store.shipping') }}</dt>
                        <dd class="font-semibold text-slate-900">
                            {{ $shippingFee > 0 ? money($shippingFee) : __('store.free') }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                        <dt class="text-base font-bold text-slate-900">{{ __('store.total') }}</dt>
                        <dd class="text-xl font-extrabold text-brand-800">{{ money($total) }}</dd>
                    </div>
                </dl>

                <button type="submit" class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl bg-brand-700 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-brand-900/20 transition hover:bg-brand-800">
                    {{ __('store.place_order') }}
                    <svg class="h-4 w-4 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                </button>

                <p class="mt-3 flex items-center justify-center gap-1.5 text-center text-[11px] text-slate-400">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                    Secure checkout — {{ setting('company_name') }}
                </p>
            </aside>
        </form>
    </div>

</x-store.layout>