<x-admin.layout title="{{ __('admin.audit_logs') }}">

    <x-admin.page-heading :title="__('admin.audit_logs')" :description="__('admin.audit_logs_page_intro')" :count="$logs->total()" :count-label="__('admin.audit_logs')" />

    <div class="ui-panel mb-5 p-3 sm:p-4">
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="flex flex-wrap items-center gap-2">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('admin.search') }}"
                class="min-w-0 flex-1 rounded-xl border-slate-200 px-3 py-2.5 text-sm focus:border-brand-500 focus:ring-brand-500 sm:max-w-sm">
            <button type="submit" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-800">{{ __('admin.search') }}</button>
        </form>
    </div>

    <div class="ui-panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50 text-start text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3">{{ __('admin.log_user') }}</th>
                        <th class="px-5 py-3">{{ __('admin.log_action') }}</th>
                        <th class="px-5 py-3">{{ __('admin.log_ip') }}</th>
                        <th class="px-5 py-3">{{ __('admin.created_at') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($logs as $log)
                        <tr>
                            <td class="px-5 py-3 text-slate-700">
                                <span class="font-semibold">{{ $log->user?->name ?? $log->user_id }}</span>
                                <span class="block text-xs text-slate-400">{{ $log->user?->email }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="font-mono text-xs font-semibold text-brand-700">{{ $log->action }}</span>
                                @if ($log->entity_type)
                                    <span class="block text-xs text-slate-400">{{ class_basename($log->entity_type) }} #{{ $log->entity_id }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 font-mono text-xs text-slate-400" dir="ltr">{{ $log->ip_address ?? '—' }}</td>
                            <td class="px-5 py-3 text-slate-400">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-sm text-slate-400">{{ __('admin.latest_orders_empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-5 py-4">
            {{ $logs->links() }}
        </div>
    </div>

</x-admin.layout>