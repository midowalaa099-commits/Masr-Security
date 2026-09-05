@php
    $colors = [
        'pending' => 'bg-slate-100 text-slate-600',
        'awaiting_payment' => 'bg-amber-100 text-amber-700',
        'paid' => 'bg-sky-100 text-sky-700',
        'processing' => 'bg-indigo-100 text-indigo-700',
        'shipped' => 'bg-purple-100 text-purple-700',
        'delivered' => 'bg-emerald-100 text-emerald-700',
        'cancelled' => 'bg-rose-100 text-rose-700',
    ];
@endphp
<span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $colors[$status->value] ?? 'bg-slate-100 text-slate-600' }}">
    {{ $status->label() }}
</span>