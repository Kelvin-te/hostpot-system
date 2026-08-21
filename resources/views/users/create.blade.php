<x-app-layout>
    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="p-4 sm:p-8">
                    @if(session('error'))
                        <div class="alert alert-danger text-red-600">
                            {{ session('error') }}
                        </div>
                    @endif

                        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight border-b-2 border-slate-100 pb-4">
                            {{ __('Create user') }}
                        </h2>

                    <form method="post" action="{{ route('users.store') }}" class="mt-6 space-y-6">
                        @csrf

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Account') }}</h2>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __("The phone number is the customer's hotspot login identity.") }}</p>
                            </div>

                            <div>
                                <div>
                                    <x-input-label for="phone" :value="__('Phone number')" class="mt-4"></x-input-label>
                                    <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone')" required></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('phone')"></x-input-error>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Subscription') }}</h2>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __("Select the package to activate for this user.") }}</p>
                            </div>

                            <div>
                                <div>
                                    <x-input-label for="package_id" :value="__('Package')" class="mt-4"></x-input-label>
                                    <select id="package_id" name="package_id" class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" required>
                                        <option value="">{{ __('Select package') }}</option>
                                        @foreach($packages as $package)
                                            <option value="{{ $package->id }}" {{ old('package_id') == $package->id ? 'selected' : '' }}>{{ $package->name }} - {{ config('app.currency') }} {{ $package->price }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error class="mt-2" :messages="$errors->get('package_id')"></x-input-error>
                                </div>

                                <div class="flex items-center gap-4 mt-4">
                                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
