<x-app-layout>
    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:bg-slate-800 dark:border-slate-700">
                <div class="p-4 sm:p-8">
                    <x-slot name="header">
                        <h2 class="text-xl font-semibold text-slate-900">
                            {{ $user->name }}
                        </h2>
                    </x-slot>
                    @if(session('error'))
                        <div class="alert alert-danger text-red-600">
                            {{ session('error') }}
                        </div>
                    @endif

                    <h2 class="text-xl font-semibold text-slate-900 border-b-2 border-slate-100 pb-4">
                        {{ __('Edit user') }}
                    </h2>

                    <form method="post" action="{{ route('users.update', $user->id) }}" class="mt-6 space-y-6">
                        @csrf
                        @method('patch')

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Account') }}</h2>
                                <p class="mt-1 text-slate-600 dark:text-slate-400">{{ __("Phone is the hotspot login identity and cannot be changed here.") }}</p>
                            </div>

                            <div>
                                <div>
                                    <x-input-label for="phone" :value="__('Phone number')" class="mt-4"></x-input-label>
                                    <x-text-input id="phone" type="text" class="mt-1 block w-full bg-slate-100" value="{{ $user->detail->phone }}" disabled></x-text-input>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Subscription') }}</h2>
                                <p class="mt-1 text-slate-600 dark:text-slate-400">{{ __("Change the active package for this user.") }}</p>
                            </div>

                            <div>
                                @include('users._form', ['user' => $user])

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
