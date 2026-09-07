<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Add Staff Member') }}</h2>
    </x-slot>

    <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

    <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 max-w-2xl">
        <form method="POST" action="{{ route('staff.store') }}" class="space-y-6">
            @csrf

            @include('staff._form')

            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                <a href="{{ route('staff.index') }}" class="text-gray-600 hover:text-gray-900">
                    {{ __('Cancel') }}
                </a>
                <x-primary-button>{{ __('Create Staff') }}</x-primary-button>
            </div>
        </form>
    </div>

    </div>
    </div>
</x-app-layout>
