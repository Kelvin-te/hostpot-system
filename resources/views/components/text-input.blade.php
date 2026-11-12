@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'border-slate-300 text-slate-800 placeholder-slate-400 focus:border-green-600 focus:ring-green-600 rounded-md shadow-sm']) !!}>
