@props([
    'title',
    'description' => null,
    'count' => null,
    'countLabel' => null,
])

<section class="relative isolate mb-6 overflow-hidden rounded-3xl border border-slate-200/80 bg-gradient-to-br from-white via-white to-brand-50/70 px-5 py-6 shadow-[0_24px_60px_-48px_rgba(15,23,42,0.35)] sm:px-7 sm:py-7">
    <div class="absolute -end-12 -top-24 -z-10 h-64 w-64 rounded-full bg-brand-200/30 blur-3xl" aria-hidden="true"></div>
    <div class="flex flex-wrap items-end justify-between gap-5">
        <div class="max-w-3xl">
            <span class="inline-flex items-center gap-2 text-[11px] font-extrabold uppercase tracking-[0.18em] text-brand-700">
                <span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>
                {{ __('admin.dashboard') }}
            </span>
            <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-950 sm:text-3xl">{{ $title }}</h1>
            @if ($description)
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">{{ $description }}</p>
            @endif
        </div>

        @if (! is_null($count))
            <div class="inline-flex min-w-28 items-center gap-3 rounded-2xl border border-white bg-white/80 px-4 py-3 shadow-sm">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-sm font-extrabold text-brand-700">{{ $count }}</span>
                @if ($countLabel)
                    <span class="text-xs font-bold leading-4 text-slate-500">{{ $countLabel }}</span>
                @endif
            </div>
        @endif

        @if ($slot->isNotEmpty())
            <div class="flex flex-wrap items-center gap-2">
                {{ $slot }}
            </div>
        @endif
    </div>
</section>
