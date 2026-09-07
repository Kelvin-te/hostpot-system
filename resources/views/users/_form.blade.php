@php
$isEdit = isset($user) && $user;
$selectedPackageId = old('package_id', $isEdit ? ($currentPackage?->id ?? '') : '');
@endphp

<div>
    <x-input-label for="package_id" :value="__('Package')" class="mt-4"></x-input-label>
    <select id="package_id" name="package_id" class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" required>
        <option value="">{{ __('Select package') }}</option>
        @foreach($packages as $package)
            <option value="{{ $package->id }}" {{ $selectedPackageId == $package->id ? 'selected' : '' }}>{{ $package->name }} - {{ config('app.currency') }} {{ $package->price }}</option>
        @endforeach
    </select>
    <x-input-error class="mt-2" :messages="$errors->get('package_id')"></x-input-error>
</div>
