<x-app-layout>
    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg">
                <div class="p-4 sm:p-8">
                    @if(session('error'))
                        <div class="alert alert-danger text-red-600">
                            {{ session('error') }}
                        </div>
                    @endif

                        <h2 class="font-semibold text-xl text-gray-800 leading-tight border-b-2 border-slate-100 pb-4">
                            {{ __('Edit Package') }}
                        </h2>

                    <form method="post" action="{{ route('packages.update', $package->id) }}" class="mt-6 space-y-6">
                        @csrf
                        @method('patch')

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h2 class="text-lg font-medium text-gray-900">{{ __('Package') }}</h2>
                                <p class="mt-1 text-sm text-gray-600">{{ __("Edit package price") }}</p>
                            </div>

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
                                    <h3 class="text-lg font-medium text-gray-900 mb-3">Bandwidth Settings</h3>
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
                                    <h3 class="text-lg font-medium text-gray-900 mb-3">Time Limits</h3>
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
                                    <h3 class="text-lg font-medium text-gray-900 mb-3">Advanced Settings</h3>
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

                                <div class="flex items-center gap-4 mt-6">
                                    <x-primary-button>{{ __('Update Hotspot Package') }}</x-primary-button>
                                    <a href="{{ route('packages.index') }}" class="text-gray-600 hover:text-gray-800">{{ __('Cancel') }}</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
