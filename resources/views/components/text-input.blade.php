@props(['disabled' => false, 'variant' => 'default'])

<input @disabled($disabled) {{ $attributes->merge(['class' => $variant === 'auth'
    ? 'h-11 rounded-xl border-slate-200 bg-white px-3.5 text-sm text-slate-900 shadow-sm transition-colors placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-200'
    : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm']) }}>
