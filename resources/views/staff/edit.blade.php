<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Staff Member') }}</h2>
    </x-slot>

    <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

    <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 max-w-2xl">
        <form method="POST" action="{{ route('staff.update', $staff) }}" class="space-y-6">
            @csrf
            @method('PUT')

            @include('staff._form')

            <div>
                <label for="is_active" class="inline-flex items-center">
                    <input id="is_active" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="is_active" value="1" {{ old('is_active', $staff->is_active) ? 'checked' : '' }}>
                    <span class="ms-2 text-sm text-gray-600">{{ __('Active') }}</span>
                </label>
                <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                <a href="{{ route('staff.index') }}" class="text-gray-600 hover:text-gray-900">
                    {{ __('Cancel') }}
                </a>
                <x-primary-button>{{ __('Update Staff') }}</x-primary-button>
            </div>
        </form>
    </div>

    </div>
    </div>
</x-app-layout>
