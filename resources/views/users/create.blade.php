<x-app-layout>
    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:bg-slate-800 dark:border-slate-700">
                <div class="p-4 sm:p-8">
                    @if(session('error'))
                        <div class="alert alert-danger text-red-600">
                            {{ session('error') }}
                        </div>
                    @endif

                        <h2 class="text-xl font-semibold text-slate-900 border-b-2 border-slate-100 pb-4">
                            {{ __('Create user') }}
                        </h2>

                    <form method="post" action="{{ route('users.store') }}" class="mt-6 space-y-6">
                        @csrf

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Account') }}</h2>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __("The phone number is the customer's hotspot login identity.") }}</p>
                            </div>

                            <div>
                                <div class="mt-4">
                                    <x-input-label for="phone" :value="__('Phone number')" class="mt-4"></x-input-label>
                                    <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone')" required></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('phone')"></x-input-error>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Subscription') }}</h2>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __("Select the package to activate for this user.") }}</p>
                            </div>

                            <div>
                                @include('users._form')

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
