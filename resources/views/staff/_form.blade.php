@php
$isEdit = isset($staff) && $staff;
$passwordLabel = $isEdit ? __('New Password') : __('Password');
@endphp

<div>
    <x-input-label for="name" :value="__('Full Name')" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $isEdit ? $staff->name : '')" required />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div>
    <x-input-label for="email" :value="__('Email')" />
    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $isEdit ? $staff->email : '')" required />
    <x-input-error :messages="$errors->get('email')" class="mt-2" />
</div>

<div>
    <x-input-label for="phone" :value="__('Phone')" />
    <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $isEdit ? $staff->phone : '')" required />
    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
</div>

<div>
    <x-input-label for="role" :value="__('Role')" />
    <select id="role" name="role" class="mt-1 block w-full rounded-md border border-gray-300">
        @php $selectedRole = old('role', $isEdit ? $staff->role : 'staff'); @endphp
        <option value="staff" {{ $selectedRole === 'staff' ? 'selected' : '' }}>{{ __('Staff') }}</option>
        <option value="admin" {{ $selectedRole === 'admin' ? 'selected' : '' }}>{{ __('Admin') }}</option>
    </select>
    <x-input-error :messages="$errors->get('role')" class="mt-2" />
</div>

@if($isEdit)
    <div class="bg-gray-50 p-4 rounded-lg">
        <p class="text-sm text-gray-600 mb-4">{{ __('Leave password fields blank to keep the current password.') }}</p>
@endif

<div>
    <x-input-label for="password" :value="$passwordLabel" />
    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" {{ $isEdit ? '' : 'required' }} autocomplete="new-password" />
    <x-input-error :messages="$errors->get('password')" class="mt-2" />
</div>

<div>
    <x-input-label for="password_confirmation" :value="$isEdit ? __('Confirm New Password') : __('Confirm Password')" />
    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" {{ $isEdit ? '' : 'required' }} autocomplete="new-password" />
</div>

@if($isEdit)
    </div>
@endif
