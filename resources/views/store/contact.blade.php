<x-store.layout>

    <section class="bg-navy-950">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-extrabold text-white">{{ __('store.contact') }}</h1>
            <p class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-300">{{ __('store.contact_description') }}</p>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-10 lg:grid-cols-3">
            <div class="space-y-4">
                @if (setting('phone'))
                    <a href="tel:{{ setting('phone') }}" class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-brand-300">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
                        </span>
                        <span>
                            <span class="block text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('store.contact_phone') }}</span>
                            <span class="mt-0.5 block font-bold text-slate-900" dir="ltr">{{ setting('phone') }}</span>
                        </span>
                    </a>
                @endif

                @if (setting('whatsapp'))
                    <a href="https://wa.me/{{ preg_replace('/\D/', '', (string) setting('whatsapp')) }}" target="_blank" rel="noopener" class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-emerald-300">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" /></svg>
                        </span>
                        <span>
                            <span class="block text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('store.contact_whatsapp') }}</span>
                            <span class="mt-0.5 block font-bold text-slate-900">{{ __('store.contact_whatsapp') }}</span>
                        </span>
                    </a>
                @endif

                @if (setting('email'))
                    <a href="mailto:{{ setting('email') }}" class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-brand-300">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
                        </span>
                        <span>
                            <span class="block text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('store.contact_email') }}</span>
                            <span class="mt-0.5 block font-bold text-slate-900" dir="ltr">{{ setting('email') }}</span>
                        </span>
                    </a>
                @endif

                @if (setting('address'))
                    <div class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                        </span>
                        <span>
                            <span class="block text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('store.contact_address') }}</span>
                            <span class="mt-0.5 block font-bold text-slate-900">{{ setting('address') }}</span>
                        </span>
                    </div>
                @endif

                @if (setting('google_maps'))
                    <iframe src="{{ setting('google_maps') }}" title="Map" class="h-64 w-full rounded-2xl border border-slate-200 shadow-sm" loading="lazy"></iframe>
                @endif
            </div>

            <div class="lg:col-span-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <h2 class="text-xl font-bold text-slate-900">{{ __('store.quote') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ __('store.quote_message') }}</p>

                    <form method="POST" action="{{ route('quote.store') }}" class="mt-6 space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="name" :value="__('store.quote_name')" />
                                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name')" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="phone" :value="__('store.phone')" />
                                <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone')" dir="ltr" required />
                                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="email" :value="__('store.email')" />
                                <x-text-input id="email" type="email" name="email" class="mt-1 block w-full" :value="old('email')" dir="ltr" />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="company" :value="__('store.quote_company')" />
                                <x-text-input id="company" name="company" class="mt-1 block w-full" :value="old('company')" />
                                <x-input-error :messages="$errors->get('company')" class="mt-2" />
                            </div>
                        </div>
                        <div>
                            <x-input-label for="message" :value="__('store.quote_message')" />
                            <textarea id="message" name="message" rows="5"
                                class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                placeholder="{{ __('store.quote_message') }}">{{ old('message') }}</textarea>
                            <x-input-error :messages="$errors->get('message')" class="mt-2" />
                        </div>

                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-700 px-6 py-3 text-sm font-bold text-white transition hover:bg-brand-800 sm:w-auto">
                            {{ __('store.quote_submit') }}
                            <svg class="h-4 w-4 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</x-store.layout>