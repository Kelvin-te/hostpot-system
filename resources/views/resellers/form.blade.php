<x-app-layout>
    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:bg-slate-800 dark:border-slate-700">
                <div class="p-6 text-slate-900 dark:text-slate-100">
                    <h2 class="font-semibold text-xl mb-6 border-b-2 border-slate-100 pb-4">
                        {{ $reseller->exists ? __('Edit Reseller') : __('Add Reseller') }}
                    </h2>

                    @if($errors->any())
                        <div class="alert alert-danger text-red-600 mb-4">{{ $errors->first() }}</div>
                    @endif

                    <form method="post" action="{{ $reseller->exists ? route('resellers.update', $reseller) : route('resellers.store') }}" class="space-y-4">
                        @csrf
                        @if($reseller->exists) @method('PUT') @endif

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="business_name" :value="__('Business name')" />
                                <x-text-input id="business_name" name="business_name" class="mt-1 block w-full" :value="old('business_name', $reseller->business_name)" required />
                                <x-input-error :messages="$errors->get('business_name')" />
                            </div>
                            <div>
                                <x-input-label for="brand_name" :value="__('Brand name (portal)')" />
                                <x-text-input id="brand_name" name="brand_name" class="mt-1 block w-full" :value="old('brand_name', $reseller->brand_name)" placeholder="{{ __('Defaults to business name') }}" />
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <x-input-label for="contact_name" :value="__('Contact person')" />
                                <x-text-input id="contact_name" name="contact_name" class="mt-1 block w-full" :value="old('contact_name', $reseller->contact_name)" />
                            </div>
                            <div>
                                <x-input-label for="phone" :value="__('Phone')" />
                                <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone', $reseller->phone)" />
                            </div>
                            <div>
                                <x-input-label for="email" :value="__('Email')" />
                                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $reseller->email)" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="status" :value="__('Status')" />
                            <select id="status" name="status" class="mt-1 block w-full rounded-md border-slate-300 dark:bg-slate-900 dark:text-slate-100">
                                <option value="active" {{ old('status', $reseller->status ?? 'active') === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                                <option value="suspended" {{ old('status', $reseller->status) === 'suspended' ? 'selected' : '' }}>{{ __('Suspended') }}</option>
                            </select>
                        </div>

                        <div class="border-t pt-4">
                            <h3 class="font-semibold mb-1">{{ __('Own M-Pesa credentials (optional)') }}</h3>
                            <p class="text-xs text-slate-500 mb-3">{{ __('Leave blank to use the ISP till. Payments then settle directly to the reseller.') }}</p>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="mpesa_shortcode" :value="__('Shortcode')" />
                                    <x-text-input id="mpesa_shortcode" name="mpesa_shortcode" class="mt-1 block w-full" :value="old('mpesa_shortcode', $reseller->mpesa_credentials['shortcode'] ?? '')" />
                                </div>
                                <div>
                                    <x-input-label for="mpesa_passkey" :value="__('Passkey')" />
                                    <x-text-input id="mpesa_passkey" name="mpesa_passkey" type="password" class="mt-1 block w-full" :value="old('mpesa_passkey')" placeholder="{{ __('Leave blank to keep existing') }}" />
                                </div>
                                <div>
                                    <x-input-label for="mpesa_consumer_key" :value="__('Consumer key')" />
                                    <x-text-input id="mpesa_consumer_key" name="mpesa_consumer_key" class="mt-1 block w-full" :value="old('mpesa_consumer_key', $reseller->mpesa_credentials['consumer_key'] ?? '')" />
                                </div>
                                <div>
                                    <x-input-label for="mpesa_consumer_secret" :value="__('Consumer secret')" />
                                    <x-text-input id="mpesa_consumer_secret" name="mpesa_consumer_secret" type="password" class="mt-1 block w-full" :value="old('mpesa_consumer_secret')" placeholder="{{ __('Leave blank to keep existing') }}" />
                                </div>
                                <div>
                                    <x-input-label for="mpesa_initiator_name" :value="__('Initiator name')" />
                                    <x-text-input id="mpesa_initiator_name" name="mpesa_initiator_name" class="mt-1 block w-full" :value="old('mpesa_initiator_name', $reseller->mpesa_credentials['initiator_name'] ?? '')" />
                                </div>
                                <div>
                                    <x-input-label for="mpesa_initiator_password" :value="__('Initiator password')" />
                                    <x-text-input id="mpesa_initiator_password" name="mpesa_initiator_password" type="password" class="mt-1 block w-full" :value="old('mpesa_initiator_password')" placeholder="{{ __('Leave blank to keep existing') }}" />
                                </div>
                                <div class="col-span-2">
                                    <x-input-label for="mpesa_callback_url" :value="__('Callback URL (optional override)')" />
                                    <x-text-input id="mpesa_callback_url" name="mpesa_callback_url" class="mt-1 block w-full" :value="old('mpesa_callback_url', $reseller->mpesa_credentials['callback_url'] ?? '')" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Save') }}</x-primary-button>
                            <a href="{{ route('resellers.index') }}" class="text-slate-600 hover:underline">{{ __('Cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
