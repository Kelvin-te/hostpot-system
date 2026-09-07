<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Package') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    ✗ {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">New Internet Package</h3>
                        <p class="text-sm text-gray-600 mt-1">Configure your new hotspot data package</p>
                    </div>
                </div>
                <div class="p-6">

                    <form method="post" action="{{ route('packages.store') }}" class="space-y-6">
                        @csrf

                        <!-- Router Selection -->
                        <div>
                            <x-input-label for="router_id" :value="__('Select Router')" class="mt-4"></x-input-label>
                            <select name="router_id" id="router_id" class="mt-1 block w-full rounded-md border border-gray-300">
                                <option value="">{{ __('Select Mikrotik router') }}</option>
                                @foreach ($routers as $router)
                                    <option value="{{ $router->id }}">{{ $router->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Package Name -->
                        <div>
                            <x-input-label for="name" :value="__('Package Name')"></x-input-label>
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required></x-text-input>
                            <x-input-error class="mt-2" :messages="$errors->get('name')"></x-input-error>
                        </div>

                        @include('packages._form')

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-6 border-t border-gray-200">
                            <a href="{{ route('packages.index') }}" class="text-gray-600 hover:text-gray-800">
                                ← Back to Packages
                            </a>
                            <x-primary-button>{{ __('✓ Create Package') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
