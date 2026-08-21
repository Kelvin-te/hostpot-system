<x-staff-app-layout>
    <h2 class="font-semibold text-xl text-gray-800 mb-6">{{ __('Edit Staff Member') }}</h2>

    <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 max-w-2xl">
        <form method="POST" action="{{ route('staff.update', $staff) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <x-input-label for="name" :value="__('Full Name')" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $staff->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $staff->email)" required />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="phone" :value="__('Phone')" />
                <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $staff->phone)" required />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="role" :value="__('Role')" />
                <select id="role" name="role" class="mt-1 block w-full rounded-md border border-gray-300">
                    <option value="staff" {{ old('role', $staff->role) === 'staff' ? 'selected' : '' }}>{{ __('Staff') }}</option>
                    <option value="admin" {{ old('role', $staff->role) === 'admin' ? 'selected' : '' }}>{{ __('Admin') }}</option>
                </select>
                <x-input-error :messages="$errors->get('role')" class="mt-2" />
            </div>

            <div>
                <label for="is_active" class="inline-flex items-center">
                    <input id="is_active" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="is_active" value="1" {{ old('is_active', $staff->is_active) ? 'checked' : '' }}>
                    <span class="ms-2 text-sm text-gray-600">{{ __('Active') }}</span>
                </label>
                <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
            </div>

            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-600 mb-4">{{ __('Leave password fields blank to keep the current password.') }}</p>

                <div>
                    <x-input-label for="password" :value="__('New Password')" />
                    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="password_confirmation" :value="__('Confirm New Password')" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                </div>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                <a href="{{ route('staff.index') }}" class="text-gray-600 hover:text-gray-900">
                    {{ __('Cancel') }}
                </a>
                <x-primary-button>{{ __('Update Staff') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-staff-app-layout>
