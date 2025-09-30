@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-bold text-gray-500']) }}>
    {{ $value ?? $slot }}
</label>
