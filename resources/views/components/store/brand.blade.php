@props(['dark' => false, 'logoClass' => 'h-10 max-w-48 object-contain object-start'])

@php
    $textColor = $dark ? 'text-white' : 'text-brand-800';
    $subColor = $dark ? 'text-slate-400' : 'text-slate-500';
    $logo = setting('site_logo');
@endphp

<a href="{{ route('home') }}" class="flex items-center gap-2.5">
    @if ($logo)
        <img src="{{ media_url($logo) }}" alt="{{ setting('company_name') ?: 'MASR Security' }}" class="{{ $logoClass }}">
    @else
        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-800 shadow-md">
            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
            </svg>
        </span>
        <span class="flex flex-col leading-tight">
            <span class="text-lg font-bold tracking-tight {{ $textColor }}">
                {{ setting('company_name') ?: 'MASR Security' }}
            </span>
            <span class="text-[11px] font-medium uppercase tracking-widest {{ $subColor }}">
                Security Solutions
            </span>
        </span>
    @endif
</a>
