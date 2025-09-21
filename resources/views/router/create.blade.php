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
                        {{ __('Add New Router') }}
                    </h2>

                    <form method="post" action="{{ route('router.store') }}" class="space-y-6">
                        @csrf

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h2 class="text-lg font-medium text-gray-900">{{ __('Router') }}</h2>
                                <p class="mt-1 text-sm text-gray-600">{{ __("Add Mikrotik router details") }}</p>
                            </div>

                            <div>
                                <div>
                                    <x-input-label for="name" :value="__('Router name')"></x-input-label>
                                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('name')"></x-input-error>
                                </div>
                                <div>
                                    <x-input-label for="location" :value="__('Location')" class="mt-4"></x-input-label>
                                    <x-text-input id="location" name="location" type="text" class="mt-1 block w-full" :value="old('location')"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('location')"></x-input-error>
                                </div>
                                <div>
                                    <x-input-label for="ip" :value="__('Router IP')" class="mt-4"></x-input-label>
                                    <x-text-input id="ip" name="ip" type="text" class="mt-1 block w-full" :value="old('ip')" required></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('ip')"></x-input-error>
                                </div>
                                <div>
                                    <x-input-label for="username" :value="__('Router username')" class="mt-4"></x-input-label>
                                    <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" :value="old('username')" required></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('username')"></x-input-error>
                                </div>
                                <div>
                                    <x-input-label for="password" :value="__('Router password')" class="mt-4"></x-input-label>
                                    <x-text-input id="password" name="password" type="text" class="mt-1 block w-full" :value="old('password')" required></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('password')"></x-input-error>
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
