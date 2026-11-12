@php
$isEdit = isset($user) && $user;
$selectedPackageId = old('package_id', $isEdit ? ($currentPackage?->id ?? '') : '');
@endphp

<div>
    <x-input-label for="connection_type" :value="__('Connection type')" class="mt-4"></x-input-label>
    <x-select id="connection_type" name="connection_type" class="mt-1 block w-full rounded-md border border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
        <option value="hotspot" {{ old('connection_type', 'hotspot') === 'hotspot' ? 'selected' : '' }}>{{ __('Hotspot (voucher / phone login)') }}</option>
        <option value="pppoe" {{ old('connection_type') === 'pppoe' ? 'selected' : '' }}>{{ __('PPPoE (fixed line)') }}</option>
    </x-select>
    <x-input-error class="mt-2" :messages="$errors->get('connection_type')"></x-input-error>
</div>

<div>
    <x-input-label for="package_id" :value="__('Package')" class="mt-4"></x-input-label>
    <x-select id="package_id" name="package_id" class="mt-1 block w-full rounded-md border border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" required>
        <option value="">{{ __('Select package') }}</option>
        @foreach($packages as $package)
            <option value="{{ $package->id }}" {{ $selectedPackageId == $package->id ? 'selected' : '' }}>{{ $package->name }} - {{ config('app.currency') }} {{ $package->price }}</option>
        @endforeach
    </x-select>
    <x-input-error class="mt-2" :messages="$errors->get('package_id')"></x-input-error>
</div>
