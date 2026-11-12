<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">{{ __('Edit Staff Member') }}</h2>
    </x-slot>

    <div class="py-8">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden p-6 max-w-2xl">
        <form method="POST" action="{{ route('staff.update', $staff) }}" class="space-y-6">
            @csrf
            @method('PUT')

            @include('staff._form')

            <div>
                <label for="is_active" class="inline-flex items-center">
                    <input id="is_active" type="checkbox" class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="is_active" value="1" {{ old('is_active', $staff->is_active) ? 'checked' : '' }}>
                    <span class="ms-2 text-sm text-slate-600">{{ __('Active') }}</span>
                </label>
                <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-slate-200">
                <div class="flex items-center gap-4">
                    <a href="{{ route('staff.index') }}" class="text-slate-600 hover:text-slate-900">
                        {{ __('Cancel') }}
                    </a>
                    <form method="POST" action="{{ route('staff.destroy', $staff) }}" onsubmit="return confirm('{{ __('Delete this staff member?') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                    </form>
                </div>
                <x-primary-button>{{ __('Update Staff') }}</x-primary-button>
            </div>
        </form>
    </div>

    </div>
    </div>
</x-app-layout>
