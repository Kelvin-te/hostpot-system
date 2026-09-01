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

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                            <div>
                                
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

                                <!-- Basic Package Info -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="name" :value="__('Package Name')" class="mt-4"></x-input-label>
                                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required></x-text-input>
                                        <x-input-error class="mt-2" :messages="$errors->get('name')"></x-input-error>
                                    </div>
                                    <div>
                                        <x-input-label for="price" :value="__('Package Price')" class="mt-4"></x-input-label>
                                        <x-text-input id="price" name="price" type="number" step="0.01" class="mt-1 block w-full" :value="old('price')" required></x-text-input>
                                        <x-input-error class="mt-2" :messages="$errors->get('price')"></x-input-error>
                                    </div>
                                </div>

                                <!-- Bandwidth Settings -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h3 class="text-md font-semibold text-gray-900 mb-3">📡 Bandwidth Settings</h3>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <x-input-label for="bandwidth_upload" :value="__('Upload Speed (Mbps)')" class="mt-2"></x-input-label>
                                            <x-text-input id="bandwidth_upload" name="bandwidth_upload" type="number" step="0.1" class="mt-1 block w-full" :value="old('bandwidth_upload')" placeholder="e.g., 10"></x-text-input>
                                            <x-input-error class="mt-2" :messages="$errors->get('bandwidth_upload')"></x-input-error>
                                        </div>
                                        <div>
                                            <x-input-label for="bandwidth_download" :value="__('Download Speed (Mbps)')" class="mt-2"></x-input-label>
                                            <x-text-input id="bandwidth_download" name="bandwidth_download" type="number" step="0.1" class="mt-1 block w-full" :value="old('bandwidth_download')" placeholder="e.g., 50"></x-text-input>
                                            <x-input-error class="mt-2" :messages="$errors->get('bandwidth_download')"></x-input-error>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-5">                                

                                <!-- Advanced Settings -->
                                <div class="bg-green-50 p-4 rounded-lg">
                                    <h3 class="text-md font-semibold text-gray-900 mb-3">⚙️ Advanced Settings</h3>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <x-input-label for="shared_users" :value="__('Shared Users')" class="mt-2"></x-input-label>
                                            <x-text-input id="shared_users" name="shared_users" type="number" class="mt-1 block w-full" :value="old('shared_users', 1)" placeholder="1"></x-text-input>
                                            <x-input-error class="mt-2" :messages="$errors->get('shared_users')"></x-input-error>
                                        </div>
                                        <div>
                                            <x-input-label for="rate_limit" :value="__('Data Cap (optional)')" class="mt-2"></x-input-label>
                                            <x-text-input id="rate_limit" name="rate_limit" type="text" class="mt-1 block w-full" :value="old('rate_limit')" placeholder="e.g., 500MB or 2GB"></x-text-input>
                                            <x-input-error class="mt-2" :messages="$errors->get('rate_limit')"></x-input-error>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                                
                        <!-- Time Limits -->
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h3 class="text-md font-semibold text-gray-900 mb-3">⏰ Time Limits</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="idle_timeout" :value="__('Idle Timeout (minutes)')" class="mt-2"></x-input-label>
                                    <x-text-input id="idle_timeout" name="idle_timeout" type="number" class="mt-1 block w-full" :value="old('idle_timeout')" placeholder="e.g., 30"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('idle_timeout')"></x-input-error>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <x-input-label for="duration_value" :value="__('Package Duration')" class="mt-2"></x-input-label>
                                        <x-text-input id="duration_value" name="duration_value" type="number" min="1" class="mt-1 block w-full" :value="old('duration_value')" placeholder="e.g., 1, 30, 90"></x-text-input>
                                        <x-input-error class="mt-2" :messages="$errors->get('duration_value')"></x-input-error>
                                    </div>
                                    <div>
                                        <x-input-label for="duration_unit" :value="__('Unit')" class="mt-2"></x-input-label>
                                        <select id="duration_unit" name="duration_unit" class="mt-1 block w-full rounded-md border border-gray-300">
                                            <option value="minutes" {{ old('duration_unit') === 'minutes' ? 'selected' : '' }}>{{ __('Minutes') }}</option>
                                            <option value="hours" {{ old('duration_unit') === 'hours' ? 'selected' : '' }}>{{ __('Hours') }}</option>
                                            <option value="days" {{ old('duration_unit', 'days') === 'days' ? 'selected' : '' }}>{{ __('Days') }}</option>
                                        </select>
                                        <x-input-error class="mt-2" :messages="$errors->get('duration_unit')"></x-input-error>
                                    </div>
                                    <p class="col-span-2 text-xs text-gray-500 mt-1">{{ __('Ranges from 1 minute up to 90 days') }}</p>
                                </div>
                            </div>
                        </div>


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
