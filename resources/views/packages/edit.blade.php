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

                        <!-- Router and Package Info (read-only) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="router_name" :value="__('Router Name')"></x-input-label>
                                <x-text-input id="router_name" name="router_name" type="text" class="mt-1 block w-full bg-gray-100" value="{{ $package->router->name }}" disabled></x-text-input>
                            </div>
                            <div>
                                <x-input-label for="package_name" :value="__('Package Name')" class="mt-0"></x-input-label>
                                <x-text-input id="package_name" name="package_name" type="text" class="mt-1 block w-full bg-gray-100" value="{{ $package->name }}" disabled></x-text-input>
                            </div>
                        </div>

                        @php
                            $currentMinutes = $package->getValidityMinutes();
                            if ($currentMinutes && $currentMinutes % 1440 === 0) {
                                $currentDurationValue = $currentMinutes / 1440;
                                $currentDurationUnit = 'days';
                            } elseif ($currentMinutes && $currentMinutes % 60 === 0) {
                                $currentDurationValue = $currentMinutes / 60;
                                $currentDurationUnit = 'hours';
                            } else {
                                $currentDurationValue = $currentMinutes;
                                $currentDurationUnit = 'minutes';
                            }
                        @endphp

                        @include('packages._form', ['package' => $package, 'currentDurationValue' => $currentDurationValue, 'currentDurationUnit' => $currentDurationUnit])

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-6 border-t border-gray-200">
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
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                        <div>
                            <h4 class="font-semibold text-gray-900">Delete Package</h4>
                            <p class="text-sm text-gray-600 mt-1">
                                Permanently delete this package from database and MikroTik router <strong>{{ $package->router->name }}</strong>. This action cannot be undone.
                            </p>
                        </div>
                        <form method="post" action="{{ route('packages.destroy', $package->id) }}" onsubmit="return confirm('Are you sure you want to delete this package? This action cannot be undone.');">
                            @csrf
                            @method('delete')
                            <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition">
                                Delete Package
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
