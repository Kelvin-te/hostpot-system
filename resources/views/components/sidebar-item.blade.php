@props(['icon' => null, 'active' => false])

@php
    $classes = ($active ?? false)
        ? 'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium bg-indigo-50 text-indigo-700'
        : 'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-400 hover:bg-white/5 hover:text-slate-100 transition-colors';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    @isset($icon)
        <x-icon :name="$icon" class="w-5 h-5 shrink-0 {{ ($active ?? false) ? 'text-indigo-600' : 'text-slate-500 group-hover:text-slate-300' }}" />
    @endisset
    <span>{{ $slot }}</span>
</a>
