<x-app-layout>
    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 sm:p-8">
                    @if(session('error'))
                        <div class="alert alert-danger text-red-600">
                            {{ session('error') }}
                        </div>
                    @endif

                    <h2 class="font-semibold text-xl text-gray-800 leading-tight border-b-2 border-slate-100 pb-4">
                        {{ __('Create Package') }}
                    </h2>

                    <form method="post" action="{{ route('packages.store') }}" class="space-y-6">
                        @csrf

                        <div>
                            <h2 class="text-lg font-medium text-gray-900">{{ __('Package') }}</h2>
                            <p class="mt-1 text-sm text-gray-600">{{ __("Add package name and price") }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">

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
                                <div class="grid grid-cols-2 gap-4">
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
                                <div class="bg-gray-50 pt-4 rounded-lg">
                                    <h3 class="text-lg font-medium text-gray-900 mb-3">Bandwidth Settings</h3>
                                    <div class="grid grid-cols-2 gap-4">
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
                                    <h3 class="text-lg font-medium text-gray-900 mb-3">Advanced Settings</h3>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <x-input-label for="shared_users" :value="__('Shared Users')" class="mt-2"></x-input-label>
                                            <x-text-input id="shared_users" name="shared_users" type="number" class="mt-1 block w-full" :value="old('shared_users', 1)" placeholder="1"></x-text-input>
                                            <x-input-error class="mt-2" :messages="$errors->get('shared_users')"></x-input-error>
                                        </div>
                                        <div>
                                            <x-input-label for="rate_limit" :value="__('Rate Limit (optional)')" class="mt-2"></x-input-label>
                                            <x-text-input id="rate_limit" name="rate_limit" type="text" class="mt-1 block w-full" :value="old('rate_limit')" placeholder="e.g., 10M/50M"></x-text-input>
                                            <x-input-error class="mt-2" :messages="$errors->get('rate_limit')"></x-input-error>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                                
                        <!-- Time Limits -->
                        <div class="bg-blue-50 p-4 my-5 rounded-lg">
                            <h3 class="text-lg font-medium text-gray-900 mb-3">Time Limits</h3>
                            <div class="grid grid-cols-4 md:grid-cols-3 gap-4">
                                <div>
                                    <x-input-label for="session_timeout" :value="__('Session Timeout (hours)')" class="mt-2"></x-input-label>
                                    <x-text-input id="session_timeout" name="session_timeout" type="number" class="mt-1 block w-full" :value="old('session_timeout')" placeholder="e.g., 24"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('session_timeout')"></x-input-error>
                                </div>
                                <div>
                                    <x-input-label for="idle_timeout" :value="__('Idle Timeout (minutes)')" class="mt-2"></x-input-label>
                                    <x-text-input id="idle_timeout" name="idle_timeout" type="number" class="mt-1 block w-full" :value="old('idle_timeout')" placeholder="e.g., 30"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('idle_timeout')"></x-input-error>
                                </div>
                                <div>
                                    <x-input-label for="validity_days" :value="__('Validity (days)')" class="mt-2"></x-input-label>
                                    <x-text-input id="validity_days" name="validity_days" type="number" class="mt-1 block w-full" :value="old('validity_days')" placeholder="e.g., 30"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('validity_days')"></x-input-error>
                                </div>
                            </div>
                        </div>


                        <div class="flex items-center gap-4 mt-6">
                            <x-primary-button>{{ __('Create Hotspot Package') }}</x-primary-button>
                            <a href="{{ route('packages.index') }}" class="text-gray-600 hover:text-gray-800">{{ __('Cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
