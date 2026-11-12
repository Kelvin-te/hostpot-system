@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-bold text-slate-500']) }}>
    {{ $value ?? $slot }}
</label>
