<div class="mb-4 space-y-2">
    <label class="block text-sm font-semibold text-slate-700">
        {{ __('admin.catalog_search') }}
        <input type="search" x-model="searchTerm" @input.debounce.300ms="searchProducts()" @keydown.enter.prevent="searchProducts()"
            class="mt-1 block w-full rounded-lg border-slate-300 px-3 py-2" :aria-busy="searching" maxlength="150">
    </label>
    <div class="flex flex-wrap items-center gap-3 text-sm">
        <button type="button" @click="searchProducts(currentPage - 1)" :disabled="currentPage === 1 || searching"
            class="rounded-lg border border-slate-200 px-3 py-2 disabled:opacity-40">{{ __('admin.catalog_previous') }}</button>
        <span aria-live="polite" x-text="currentPage"></span>
        <button type="button" @click="searchProducts(currentPage + 1)" :disabled="!hasMore || searching"
            class="rounded-lg border border-slate-200 px-3 py-2 disabled:opacity-40">{{ __('admin.catalog_next') }}</button>
        <span x-show="searching" role="status">{{ __('admin.catalog_loading') }}</span>
        <span x-cloak x-show="searchError" role="alert" class="text-rose-700">{{ __('admin.catalog_error') }}</span>
    </div>
</div>
