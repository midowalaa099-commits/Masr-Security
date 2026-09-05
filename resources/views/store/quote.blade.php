<x-store.layout>

    <section class="bg-navy-950">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-extrabold text-white">{{ __('store.quote') }}</h1>
            <p class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-300">{{ __('store.quote_message') }}</p>
        </div>
    </section>

    <div class="mx-auto max-w-2xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <form method="POST" action="{{ route('quote.store') }}" class="space-y-4">
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
                    <textarea id="message" name="message" rows="6"
                        class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        placeholder="{{ __('store.quote_message') }}">{{ old('message') }}</textarea>
                    <x-input-error :messages="$errors->get('message')" class="mt-2" />
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-700 px-6 py-3 text-sm font-bold text-white transition hover:bg-brand-800">
                        {{ __('store.quote_submit') }}
                    </button>
                    <a href="{{ route('contact') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">{{ __('store.contact') }}</a>
                </div>
            </form>
        </div>
    </div>

</x-store.layout>