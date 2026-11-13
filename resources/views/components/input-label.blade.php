@props(['value'])

<label {{ $attributes->merge(['class' => 'block  font-medium text-slate-700']) }}>
    {{ $value ?? $slot }}
</label>
