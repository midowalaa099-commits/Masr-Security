<nav class="w-full shrink-0 space-y-1 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm lg:w-56">
    <a href="{{ route('account.dashboard') }}"
        class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm font-semibold {{ request()->routeIs('account.dashboard') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50' }}">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
        {{ __('store.account') }}
    </a>
    <a href="{{ route('account.orders') }}"
        class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm font-semibold {{ request()->routeIs('account.orders*') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50' }}">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
        {{ __('store.orders') }}
    </a>
    <a href="{{ route('account.dashboard') }}#account-security"
        class="group flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-brand-50 hover:text-brand-700">
        <svg class="h-4 w-4 transition group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 00-9 0v3.75m-.75 10.125h10.5A2.625 2.625 0 0019.875 18V12.75a2.625 2.625 0 00-2.625-2.625H6.75a2.625 2.625 0 00-2.625 2.625V18a2.625 2.625 0 002.625 2.625z" /></svg>
        {{ __('store.change_password') }}
    </a>
    <a href="{{ route('quote.create') }}"
        class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm font-semibold {{ request()->routeIs('quote.*') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50' }}">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
        {{ __('store.quote') }}
    </a>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-500 hover:bg-rose-50 hover:text-rose-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" /></svg>
            {{ __('store.logout') }}
        </button>
    </form>
</nav>
