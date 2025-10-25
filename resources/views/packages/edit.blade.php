<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Package') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    ✗ {{ session('error') }}
                </div>
            @endif

            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                    ✓ {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Edit Package: {{ $package->name }}</h3>
                        <p class="text-sm text-gray-600 mt-1">Update package settings and pricing</p>
                    </div>
                </div>
                <div class="p-6">
                    <form method="post" action="{{ route('packages.update', $package->id) }}" class="space-y-6">
                        @csrf
                        @method('patch')

                        <div class="space-y-4">
                                <!-- Router and Package Info -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="router_name" :value="__('Router Name')"></x-input-label>
                                        <x-text-input id="router_name" name="router_name" type="text" class="mt-1 block w-full bg-gray-100" value="{{ $package->router->name }}" disabled></x-text-input>
                                    </div>
                                    <div>
                                        <x-input-label for="package_name" :value="__('Package Name')" class="mt-0"></x-input-label>
                                        <x-text-input id="package_name" name="package_name" type="text" class="mt-1 block w-full bg-gray-100" value="{{ $package->name }}" disabled></x-text-input>
                                    </div>
                                </div>

                                <!-- Price -->
                                <div>
                                    <x-input-label for="price" :value="__('Package Price')" class="mt-4"></x-input-label>
                                    <x-text-input id="price" name="price" type="number" step="0.01" class="mt-1 block w-full" value="{{ $package->price }}" required></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('price')"></x-input-error>
                                </div>

                                <!-- Bandwidth Settings -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h3 class="text-md font-semibold text-gray-900 mb-3">📡 Bandwidth Settings</h3>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <x-input-label for="bandwidth_upload" :value="__('Upload Speed (Mbps)')" class="mt-2"></x-input-label>
                                            <x-text-input id="bandwidth_upload" name="bandwidth_upload" type="number" step="0.1" class="mt-1 block w-full" value="{{ $package->bandwidth_upload }}" placeholder="e.g., 10"></x-text-input>
                                            <x-input-error class="mt-2" :messages="$errors->get('bandwidth_upload')"></x-input-error>
                                        </div>
                                        <div>
                                            <x-input-label for="bandwidth_download" :value="__('Download Speed (Mbps)')" class="mt-2"></x-input-label>
                                            <x-text-input id="bandwidth_download" name="bandwidth_download" type="number" step="0.1" class="mt-1 block w-full" value="{{ $package->bandwidth_download }}" placeholder="e.g., 50"></x-text-input>
                                            <x-input-error class="mt-2" :messages="$errors->get('bandwidth_download')"></x-input-error>
                                        </div>
                                    </div>
                                </div>

                                <!-- Time Limits -->
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <h3 class="text-md font-semibold text-gray-900 mb-3">⏰ Time Limits</h3>
                                    <div class="grid grid-cols-3 gap-4">
                                        <div>
                                            <x-input-label for="session_timeout" :value="__('Session Timeout (hours)')" class="mt-2"></x-input-label>
                                            <x-text-input id="session_timeout" name="session_timeout" type="number" class="mt-1 block w-full" value="{{ $package->session_timeout }}" placeholder="e.g., 24"></x-text-input>
                                            <x-input-error class="mt-2" :messages="$errors->get('session_timeout')"></x-input-error>
                                        </div>
                                        <div>
                                            <x-input-label for="idle_timeout" :value="__('Idle Timeout (minutes)')" class="mt-2"></x-input-label>
                                            <x-text-input id="idle_timeout" name="idle_timeout" type="number" class="mt-1 block w-full" value="{{ $package->idle_timeout }}" placeholder="e.g., 30"></x-text-input>
                                            <x-input-error class="mt-2" :messages="$errors->get('idle_timeout')"></x-input-error>
                                        </div>
                                        <div>
                                            <x-input-label for="validity_days" :value="__('Validity (days)')" class="mt-2"></x-input-label>
                                            <x-text-input id="validity_days" name="validity_days" type="number" class="mt-1 block w-full" value="{{ $package->validity_days }}" placeholder="e.g., 30"></x-text-input>
                                            <x-input-error class="mt-2" :messages="$errors->get('validity_days')"></x-input-error>
                                        </div>
                                    </div>
                                </div>

                                <!-- Advanced Settings -->
                                <div class="bg-green-50 p-4 rounded-lg">
                                    <h3 class="text-md font-semibold text-gray-900 mb-3">⚙️ Advanced Settings</h3>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <x-input-label for="shared_users" :value="__('Shared Users')" class="mt-2"></x-input-label>
                                            <x-text-input id="shared_users" name="shared_users" type="number" class="mt-1 block w-full" value="{{ $package->shared_users ?: 1 }}" placeholder="1"></x-text-input>
                                            <x-input-error class="mt-2" :messages="$errors->get('shared_users')"></x-input-error>
                                        </div>
                                        <div>
                                            <x-input-label for="rate_limit" :value="__('Rate Limit (optional)')" class="mt-2"></x-input-label>
                                            <x-text-input id="rate_limit" name="rate_limit" type="text" class="mt-1 block w-full" value="{{ $package->rate_limit }}" placeholder="e.g., 10M/50M"></x-text-input>
                                            <x-input-error class="mt-2" :messages="$errors->get('rate_limit')"></x-input-error>
                                        </div>
                                    </div>
                                </div>

                        <div class="flex items-center justify-between gap-4 pt-6 border-t border-gray-200">
                            <a href="{{ route('packages.index') }}" class="text-gray-600 hover:text-gray-800">
                                ← Back to Packages
                            </a>
                            <x-primary-button>{{ __('✓ Update Package') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Danger Zone: Delete Package -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg border-2 border-red-200 m-6">
                <div class="p-6 border-b border-red-200 bg-red-50">
                    <h3 class="text-lg font-semibold text-red-900">🗑️ Danger Zone</h3>
                </div>
                <div class="p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h4 class="font-semibold text-gray-900">Delete Package</h4>
                            <p class="text-sm text-gray-600 mt-1">
                                Permanently delete this package from database and MikroTik router <strong>{{ $package->router->name }}</strong>. This action cannot be undone.
                            </p>
                        </div>
                        <form method="post" action="{{ route('packages.destroy', $package->id) }}" onsubmit="return confirm('Are you sure you want to delete this package? This action cannot be undone.');">
                            @csrf
                            @method('delete')
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition">
                                Delete Package
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
