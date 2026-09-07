@php
$isEdit = isset($package) && $package;
$durationValue = old('duration_value', $isEdit ? ($currentDurationValue ?? null) : null);
$durationUnit = old('duration_unit', $isEdit ? ($currentDurationUnit ?? 'days') : 'days');
@endphp

<!-- Price -->
<div>
    <x-input-label for="price" :value="__('Package Price (KES)')" class="mt-4"></x-input-label>
    <x-text-input id="price" name="price" type="number" step="0.01" class="mt-1 block w-full" :value="old('price', $isEdit ? $package->price : '')" required></x-text-input>
    <p class="text-xs text-gray-500 mt-1">{{ __('Amount the customer pays via M-Pesa. Use whole numbers or up to 2 decimals (e.g., 50 or 99.50).') }}</p>
    <x-input-error class="mt-2" :messages="$errors->get('price')"></x-input-error>
</div>

<!-- Bandwidth Settings -->
<div class="bg-gray-50 p-4 rounded-lg">
    <h3 class="text-md font-semibold text-gray-900 mb-3">📡 Bandwidth Settings</h3>
    <p class="text-xs text-gray-500 mb-3">{{ __('Speed limits applied to each user. Enter whole numbers only (e.g., 5, 10, 50). Leave blank for unlimited.') }}</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="bandwidth_upload" :value="__('Upload Speed (Mbps)')" class="mt-2"></x-input-label>
            <x-text-input id="bandwidth_upload" name="bandwidth_upload" type="number" min="0" step="1" class="mt-1 block w-full" :value="old('bandwidth_upload', $isEdit ? $package->bandwidth_upload : '')" placeholder="e.g., 5"></x-text-input>
            <p class="text-xs text-gray-500 mt-1">{{ __('Whole Mbps. 0 or blank = unlimited upload.') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('bandwidth_upload')"></x-input-error>
        </div>
        <div>
            <x-input-label for="bandwidth_download" :value="__('Download Speed (Mbps)')" class="mt-2"></x-input-label>
            <x-text-input id="bandwidth_download" name="bandwidth_download" type="number" min="0" step="1" class="mt-1 block w-full" :value="old('bandwidth_download', $isEdit ? $package->bandwidth_download : '')" placeholder="e.g., 10"></x-text-input>
            <p class="text-xs text-gray-500 mt-1">{{ __('Whole Mbps. 0 or blank = unlimited download.') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('bandwidth_download')"></x-input-error>
        </div>
    </div>
</div>

<!-- Time Limits -->
<div class="bg-blue-50 p-4 rounded-lg">
    <h3 class="text-md font-semibold text-gray-900 mb-3">⏰ Time Limits</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="grid grid-cols-2 gap-2">
            <div>
                <x-input-label for="duration_value" :value="__('Package Duration')" class="mt-2"></x-input-label>
                <x-text-input id="duration_value" name="duration_value" type="number" min="1" class="mt-1 block w-full" :value="$durationValue" placeholder="e.g., 1, 30, 90"></x-text-input>
                <x-input-error class="mt-2" :messages="$errors->get('duration_value')"></x-input-error>
            </div>
            <div>
                <x-input-label for="duration_unit" :value="__('Unit')" class="mt-2"></x-input-label>
                <select id="duration_unit" name="duration_unit" class="mt-1 block w-full rounded-md border border-gray-300">
                    <option value="minutes" {{ $durationUnit === 'minutes' ? 'selected' : '' }}>{{ __('Minutes') }}</option>
                    <option value="hours" {{ $durationUnit === 'hours' ? 'selected' : '' }}>{{ __('Hours') }}</option>
                    <option value="days" {{ $durationUnit === 'days' ? 'selected' : '' }}>{{ __('Days') }}</option>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('duration_unit')"></x-input-error>
            </div>
            <p class="col-span-2 text-xs text-gray-500 mt-1">{{ __('How long the package stays active. Users are automatically disconnected when the package expires. Ranges from 1 minute up to 90 days.') }}</p>
        </div>
    </div>
</div>

<!-- Advanced Settings -->
<div class="bg-green-50 p-4 rounded-lg">
    <h3 class="text-md font-semibold text-gray-900 mb-3">⚙️ Advanced Settings</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="shared_users" :value="__('Shared Users')" class="mt-2"></x-input-label>
            <x-text-input id="shared_users" name="shared_users" type="number" min="1" class="mt-1 block w-full" :value="old('shared_users', $isEdit ? $package->shared_users : 1)" placeholder="1"></x-text-input>
            <p class="text-xs text-gray-500 mt-1">{{ __('Max simultaneous logins per credential. Default: 1.') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('shared_users')"></x-input-error>
        </div>
        <div>
            <x-input-label for="data_cap" :value="__('Data Cap (optional)')" class="mt-2"></x-input-label>
            <x-text-input id="data_cap" name="data_cap" type="text" class="mt-1 block w-full" :value="old('data_cap', $isEdit ? $package->data_cap : '')" placeholder="e.g., 500MB or 2GB"></x-text-input>
            <p class="text-xs text-gray-500 mt-1">{{ __('Total data the user can consume. Format: number + MB or GB (e.g., 500MB, 2GB). Leave blank for unlimited.') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('data_cap')"></x-input-error>
        </div>
    </div>
</div>
