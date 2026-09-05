<x-admin.layout title="{{ __('admin.quotes') }}">

    @php
        $badges = [
            'new' => 'bg-brand-100 text-brand-700',
            'contacted' => 'bg-amber-100 text-amber-700',
            'closed' => 'bg-slate-100 text-slate-500',
        ];
    @endphp

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.quotes.index') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-700">← {{ __('admin.back') }}</a>
            <h1 class="text-lg font-extrabold text-slate-900">{{ $quote->name }}</h1>
        </div>
        <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $badges[$quote->status->value] ?? 'bg-slate-100 text-slate-500' }}">{{ $quote->status->label() }}</span>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('admin.quote_details') }}</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('admin.customer_name_label') }}</dt>
                    <dd class="mt-0.5 font-semibold text-slate-800">{{ $quote->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('admin.company') }}</dt>
                    <dd class="mt-0.5 text-slate-700">{{ $quote->company ?: '—' }}</dd>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('admin.phone') }}</dt>
                        <dd class="mt-0.5 text-slate-700" dir="ltr">{{ $quote->phone }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('admin.email') }}</dt>
                        <dd class="mt-0.5 text-slate-700" dir="ltr">{{ $quote->email ?? '—' }}</dd>
                    </div>
                </div>
                @if ($quote->requested_products)
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('admin.requested_products') }}</dt>
                        <dd class="mt-0.5 flex flex-wrap gap-1.5">
                            @foreach ($quote->requested_products as $productId)
                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">#{{ $productId }}</span>
                            @endforeach
                        </dd>
                    </div>
                @endif
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('admin.message') }}</dt>
                    <dd class="mt-0.5 whitespace-pre-line text-slate-700">{{ $quote->message ?: '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm self-start">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('admin.update_status') }}</h2>
            <form method="POST" action="{{ route('admin.quotes.status', $quote) }}" class="mt-4 space-y-4">
                @csrf
                <select name="status" class="block w-full rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500">
                    @foreach (\App\Enums\QuoteStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected($quote->status === $status)>{{ $status->label() }}</option>
                    @endforeach
                </select>
                <button type="submit" class="w-full rounded-lg bg-brand-700 px-4 py-2 text-sm font-bold text-white hover:bg-brand-800">{{ __('admin.save') }}</button>
            </form>
        </div>
    </div>

</x-admin.layout>