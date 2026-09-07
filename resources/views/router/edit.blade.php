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
                            {{ __('Edit Router') }}
                        </h2>

                    <form method="post" action="{{ route('router.update', $router->id) }}" class="mt-6 space-y-6">
                        @csrf
                        @method('put')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <h2 class="text-lg font-medium text-gray-900">{{ __('Router') }}</h2>
                                <p class="mt-1 text-sm text-gray-600">{{ __("Edit Mikrotik router details") }}</p>
                            </div>

                            <div>
                                <div>
                                    <x-input-label for="name" :value="__('Router name')"></x-input-label>
                                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full bg-gray-100" value="{{ $router->name }}" disabled></x-text-input>
                                </div>
                                <div>
                                    <x-input-label for="identifier" :value="__('Router identifier')" class="mt-4"></x-input-label>
                                    <x-text-input id="identifier" name="identifier" type="text" class="mt-1 block w-full bg-gray-100 font-mono" value="{{ $router->identifier }}" disabled></x-text-input>
                                    <p class="mt-1 text-xs text-gray-500">{{ __('Auto-generated. Copy this value into the router\'s MikroTik login.html file (the "router" hidden field) so the hotspot login page identifies this router correctly.') }}</p>
                                </div>

                                @include('router._form')

                                <div class="flex items-center gap-4 mt-4">
                                    <x-primary-button>{{ __('Update') }}</x-primary-button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
