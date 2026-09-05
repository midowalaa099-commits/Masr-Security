<x-store.layout>

    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <nav class="text-xs text-slate-400">
                <a href="{{ route('home') }}" class="hover:text-brand-600">{{ __('store.home') }}</a>
                <span class="mx-1.5">/</span>
                <a href="{{ route('shop') }}" class="hover:text-brand-600">{{ __('store.shop') }}</a>
                <span class="mx-1.5">/</span>
                <span class="text-slate-600">{{ $category->trans('name') }}</span>
            </nav>
            <h1 class="mt-3 text-2xl font-bold text-slate-900">{{ $category->trans('name') }}</h1>
            @if ($category->trans('description'))
                <p class="mt-1 max-w-2xl text-sm text-slate-500">{{ $category->trans('description') }}</p>
            @endif
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if ($products->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white py-16 text-center">
                <svg class="h-14 w-14 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75" /></svg>
                <p class="mt-4 text-sm font-semibold text-slate-700">{{ __('store.no_products') }}</p>
                <a href="{{ route('shop') }}" class="mt-3 text-sm font-semibold text-brand-700 hover:text-brand-800">{{ __('store.view_all') }}</a>
            </div>
        @else
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($products as $product)
                    <x-store.product-card :product="$product" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $products->links() }}
            </div>
        @endif
    </div>

</x-store.layout>