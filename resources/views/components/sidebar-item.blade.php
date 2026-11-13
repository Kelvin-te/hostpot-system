@props(['icon' => null, 'active' => false])

@php
    $classes = ($active ?? false)
        ? 'group flex items-center gap-3 rounded-lg px-3 py-2 font-medium bg-indigo-50 text-indigo-700'
        : 'group flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    @isset($icon)
        <x-app-icon :name="$icon" class="w-5 h-5 shrink-0 {{ ($active ?? false) ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600' }}" />
    @endisset
    <span>{{ $slot }}</span>
</a>
