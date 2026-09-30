<x-admin.layout :title="__('pricing.title').' #'.$batch->id">
    <a href="{{ route('admin.pricing.index') }}" class="text-brand-700 underline">{{ __('pricing.title') }}</a>
    <p class="my-4">{{ __('pricing.'.$options['scope']) }} · {{ __('pricing.'.$options['direction']) }} {{ $options['amount'] }} {{ $options['operation'] === 'percentage' ? '%' : 'EGP' }} · {{ __('pricing.'.$options['target']) }} · {{ __('pricing.rounding') }}: {{ __('pricing.'.$options['rounding']) }} {{ $options['precision'] ?? '' }}</p>
    @if (isset($options['brand'])) <p>{{ $options['brand'] }}</p> @endif
    @if (isset($options['category_id'])) <p>{{ __('pricing.category') }} #{{ $options['category_id'] }}</p> @endif
    <p class="my-4 text-sm text-slate-600">{{ __('pricing.help') }}</p>
    <p>{{ auth()->user()->name }} · {{ __('pricing.'.$batch->status) }} · {{ $batch->created_at }}</p>
    @if ($batch->applied_at)<p>{{ __('pricing.applied') }}: {{ $batch->applied_at }}</p>@endif
    @if ($batch->undone_at)<p>{{ __('pricing.undone') }}: {{ $batch->undone_at }}</p>@endif
    <ul class="my-4 flex flex-wrap gap-4">
        @foreach ($counts as $status => $total)
            <li>{{ __('pricing.'.$status) }}: <strong>{{ $total }}</strong></li>
        @endforeach
    </ul>
    @if ($batch->status === 'preview' && now()->lessThanOrEqualTo($batch->expires_at) && ($counts['ready'] ?? 0) > 0)
        <form method="POST" action="{{ route('admin.pricing.apply', $batch->id) }}" class="my-5 grid gap-3">
            @csrf
            <input type="hidden" name="token" value="{{ $batch->token }}">
            <label><input type="checkbox" name="confirmed" value="1" required> {{ __('pricing.confirm_apply') }}</label>
            <p class="text-sm">{{ __('pricing.expires') }} {{ $batch->expires_at }}</p>
            <button class="w-fit rounded-lg bg-brand-700 px-5 py-2 font-bold text-white">{{ __('pricing.apply') }}</button>
        </form>
    @elseif ($batch->status === 'preview' && now()->greaterThan($batch->expires_at))
        <p class="my-4 text-rose-700">{{ __('pricing.expired') }}</p>
    @elseif ($batch->status === 'applied')
        <form method="POST" action="{{ route('admin.pricing.undo', $batch->id) }}" class="my-5 grid gap-3">
            @csrf
            <input type="hidden" name="token" value="{{ $batch->token }}">
            <label><input type="checkbox" name="confirmed" value="1" required> {{ __('pricing.confirm_undo') }}</label>
            <button class="w-fit rounded-lg bg-slate-800 px-5 py-2 font-bold text-white">{{ __('pricing.undo') }}</button>
        </form>
    @endif
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="w-full text-start text-sm">
            <thead><tr>
                @foreach (['sku', 'before', 'after', 'status', 'reason'] as $heading)
                    <th class="p-3 text-start">{{ __('pricing.'.$heading) }}</th>
                @endforeach
            </tr></thead>
            <tbody>
                @forelse ($items as $item)
                    @php
                        $before = json_decode($item->before, true);
                        $after = json_decode($item->after, true);
                    @endphp
                    <tr class="border-t border-slate-100">
                        <td class="p-3">{{ $item->sku }}</td>
                        @foreach ([$before, $after] as $prices)
                            <td class="p-3">{{ __('pricing.regular') }}: {{ $prices['price'] }} EGP<br>{{ __('pricing.sale') }}: {{ $prices['sale_price'] ?? '—' }}</td>
                        @endforeach
                        <td class="p-3">{{ __('pricing.'.$item->status) }}</td>
                        <td class="p-3">{{ $item->reason ? __('pricing.'.$item->reason) : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-5">{{ __('admin.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="my-4">{{ $items->links() }}</div>
</x-admin.layout>
