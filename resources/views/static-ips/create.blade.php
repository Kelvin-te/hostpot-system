<x-app-layout>
    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:bg-slate-800 dark:border-slate-700">
                <div class="p-6 text-slate-900 dark:text-slate-100">
                    <h2 class="font-semibold text-xl mb-6 border-b-2 border-slate-100 pb-4">{{ __('Add static IP') }}</h2>

                    @if($errors->any())
                        <div class="alert alert-danger text-red-600 mb-4">{{ $errors->first() }}</div>
                    @endif

                    <form method="post" action="{{ route('static-ips.store') }}" class="space-y-4">
                        @csrf

                        <div>
                            <x-input-label for="router_id" :value="__('Router')" />
                            <select id="router_id" name="router_id" class="mt-1 block w-full rounded-md border-slate-300 dark:bg-slate-900 dark:text-slate-100" required>
                                <option value="">{{ __('Select router') }}</option>
                                @foreach($routers as $router)
                                    <option value="{{ $router->id }}" {{ old('router_id') == $router->id ? 'selected' : '' }}>{{ $router->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('router_id')" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="ip_address" :value="__('IP address')" />
                                <x-text-input id="ip_address" name="ip_address" class="mt-1 block w-full" :value="old('ip_address')" required />
                                <x-input-error :messages="$errors->get('ip_address')" />
                            </div>
                            <div>
                                <x-input-label for="mac_address" :value="__('MAC address')" />
                                <x-text-input id="mac_address" name="mac_address" class="mt-1 block w-full" :value="old('mac_address')" placeholder="AA:BB:CC:DD:EE:FF" />
                                <x-input-error :messages="$errors->get('mac_address')" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="user_id" :value="__('Subscriber (optional)')" />
                                <select id="user_id" name="user_id" class="mt-1 block w-full rounded-md border-slate-300 dark:bg-slate-900 dark:text-slate-100">
                                    <option value="">{{ __('—') }}</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->phone }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="package_id" :value="__('Package (optional)')" />
                                <select id="package_id" name="package_id" class="mt-1 block w-full rounded-md border-slate-300 dark:bg-slate-900 dark:text-slate-100">
                                    <option value="">{{ __('—') }}</option>
                                    @foreach($packages as $package)
                                        <option value="{{ $package->id }}" {{ old('package_id') == $package->id ? 'selected' : '' }}>{{ $package->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="status" :value="__('Status')" />
                                <select id="status" name="status" class="mt-1 block w-full rounded-md border-slate-300 dark:bg-slate-900 dark:text-slate-100" required>
                                    @foreach(['free', 'assigned', 'reserved'] as $s)
                                        <option value="{{ $s }}" {{ old('status', 'free') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="comment" :value="__('Comment')" />
                                <x-text-input id="comment" name="comment" class="mt-1 block w-full" :value="old('comment')" />
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="sync_to_router" name="sync_to_router" value="1" {{ old('sync_to_router') ? 'checked' : '' }} class="rounded border-slate-300">
                            <x-input-label for="sync_to_router" :value="__('Push DHCP static lease to router now (requires MAC)')" class="!mt-0" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Save') }}</x-primary-button>
                            <a href="{{ route('static-ips.index') }}" class="text-slate-600 hover:underline">{{ __('Cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
