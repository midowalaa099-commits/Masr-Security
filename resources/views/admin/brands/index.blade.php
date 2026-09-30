<x-admin.layout title="{{ __('admin.brands') }}">
    <x-admin.page-heading
        :title="__('admin.brands')"
        :description="__('admin.brand_catalog_intro')"
        :count="$brands->count()"
        :count-label="__('admin.brands')"
    />

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
        <section class="ui-panel overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div>
                    <h2 class="text-base font-bold text-slate-900">{{ __('admin.brands') }}</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ __('admin.brand_catalog_intro') }}</p>
                </div>
                <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-bold text-brand-700">{{ $brands->count() }} {{ __('admin.brands') }}</span>
            </div>

            <ul class="divide-y divide-slate-100">
                @forelse ($brands as $brand)
                    <li class="flex flex-wrap items-center justify-between gap-4 px-5 py-4 transition hover:bg-slate-50/80 sm:px-6">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-brand-50 to-brand-100 text-sm font-extrabold text-brand-800">{{ mb_substr($brand->name, 0, 1) }}</span>
                            <div class="min-w-0">
                                <h3 class="truncate font-bold text-slate-800" dir="ltr">{{ $brand->name }}</h3>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $brand->products_count }} {{ __('admin.products') }}</p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('admin.brands.destroy', $brand) }}" onsubmit="return confirm('{{ __('store.are_you_sure') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-rose-200 px-3 py-2 text-xs font-bold text-rose-700 transition hover:border-rose-300 hover:bg-rose-50">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12m-10 0 .6 12h6.8L16 7M9 7V4.75A.75.75 0 019.75 4h4.5a.75.75 0 01.75.75V7m-5 3v5m4-5v5" /></svg>
                                {{ __('admin.delete') }}
                            </button>
                        </form>
                    </li>
                @empty
                    <li class="px-6 py-12 text-center">
                        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3.48a3.75 3.75 0 015.304 0l5.648 5.648a3.75 3.75 0 010 5.304l-5.648 5.648a3.75 3.75 0 01-5.304 0L3.92 14.432a3.75 3.75 0 010-5.304l5.648-5.648z" /></svg>
                        </span>
                        <p class="mt-3 text-sm font-bold text-slate-700">{{ __('admin.no_records') }}</p>
                    </li>
                @endforelse
            </ul>
        </section>

        <aside class="ui-panel p-5 sm:p-6">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            </span>
            <h2 class="mt-4 text-lg font-extrabold text-slate-900">{{ __('admin.brand_add_title') }}</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('admin.brand_add_intro') }}</p>

            <form method="POST" action="{{ route('admin.brands.store') }}" class="mt-5 space-y-4">
                @csrf
                <div>
                    <x-input-label for="name" :value="__('admin.brand_name')" />
                    <x-text-input id="name" name="name" class="mt-1.5 block w-full" :value="old('name')" maxlength="100" dir="auto" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <button type="submit" class="ui-button-primary w-full">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    {{ __('admin.brand_add') }}
                </button>
            </form>

            <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3 text-xs leading-5 text-amber-900">
                {{ __('admin.brand_delete_hint') }}
            </div>
        </aside>
    </div>
</x-admin.layout>
