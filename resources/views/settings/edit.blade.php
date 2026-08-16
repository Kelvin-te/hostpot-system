<x-app-layout>
    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 sm:p-8">
                    @if(session('success'))
                        <div class="alert alert-success text-gray-400">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger text-red-600">
                            {{ session('error') }}
                        </div>
                    @endif

                        <h2 class="font-semibold text-xl text-gray-800 leading-tight border-b-2 border-slate-100 pb-4">
                            {{ __('Application Settings') }}
                        </h2>

                    <form method="post" action="{{ route('settings.update') }}" class="mt-6 space-y-6">
                        @csrf
                        @method('patch')

                        <!-- Company Information Section -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h2 class="text-lg font-medium text-gray-900">{{ __('ISP Information') }}</h2>
                                <p class="mt-1 text-sm text-gray-600">{{ __("Update your company information.") }}</p>
                            </div>

                            <div>
                                <div>
                                    <x-input-label for="company_name" value="{{ __('ISP name') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="company_name" name="company_name" type="text" class="mt-1 block w-full" value="{{ old('company_name', $settings->company_name ?? '') }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('company_name')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="company_address" value="{{ __('Address') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="company_address" name="company_address" type="text" class="mt-1 block w-full" value="{{ old('company_address', $settings->company_address ?? '') }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('company_address')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="company_email" value="{{ __('Email address') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="company_email" name="company_email" type="email" class="mt-1 block w-full" value="{{ old('company_email', $settings->company_email ?? '') }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('company_email')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="company_phone" value="{{ __('Phone number') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="company_phone" name="company_phone" type="text" class="mt-1 block w-full" value="{{ old('company_phone', $settings->company_phone ?? '') }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('company_phone')"></x-input-error>
                                </div>
                            </div>
                        </div>

                        <hr class="my-6">

                        <!-- Application Settings Section -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h2 class="text-lg font-medium text-gray-900">{{ __('Application Settings') }}</h2>
                                <p class="mt-1 text-sm text-gray-600">{{ __("Configure global application settings.") }}</p>
                            </div>

                            <div>
                                <div>
                                    <x-input-label for="app_name" value="{{ __('Application name') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="app_name" name="app_name" type="text" class="mt-1 block w-full" value="{{ old('app_name', $settings->app_name ?? config('app.name')) }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('app_name')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="timezone" value="{{ __('Timezone') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="timezone" name="timezone" type="text" class="mt-1 block w-full" value="{{ old('timezone', $settings->timezone ?? config('app.timezone')) }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('timezone')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="currency" value="{{ __('Currency') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="currency" name="currency" type="text" class="mt-1 block w-full" value="{{ old('currency', $settings->currency ?? 'KES') }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('currency')"></x-input-error>
                                </div>
                            </div>
                        </div>

                        <hr class="my-6">

                        <!-- Walled Garden Settings Section -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h2 class="text-lg font-medium text-gray-900">{{ __('Walled Garden Settings') }}</h2>
                                <p class="mt-1 text-sm text-gray-600">{{ __("Configure walled garden for captive portal.") }}</p>
                            </div>

                            <div>
                                <div>
                                    <x-input-label for="walled_garden_enabled" value="{{ __('Enable Walled Garden') }}" class="mt-4"></x-input-label>
                                    <select id="walled_garden_enabled" name="walled_garden_enabled" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                        <option value="1" {{ old('walled_garden_enabled', $settings->walled_garden_enabled ?? true) ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ !old('walled_garden_enabled', $settings->walled_garden_enabled ?? true) ? 'selected' : '' }}>No</option>
                                    </select>
                                    <x-input-error class="mt-2" :messages="$errors->get('walled_garden_enabled')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="walled_garden_domains" value="{{ __('Walled Garden Domains') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="walled_garden_domains" name="walled_garden_domains" type="text" class="mt-1 block w-full" value="{{ old('walled_garden_domains', $settings->walled_garden_domains ? implode(', ', json_decode($settings->walled_garden_domains)) : '') }}" placeholder="comma-separated domains"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('walled_garden_domains')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="walled_garden_ips" value="{{ __('Walled Garden IPs') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="walled_garden_ips" name="walled_garden_ips" type="text" class="mt-1 block w-full" value="{{ old('walled_garden_ips', $settings->walled_garden_ips ? implode(', ', json_decode($settings->walled_garden_ips)) : '') }}" placeholder="comma-separated IPs"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('walled_garden_ips')"></x-input-error>
                                </div>
                            </div>
                        </div>

                        <hr class="my-6">

                        <!-- Billing Settings Section -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h2 class="text-lg font-medium text-gray-900">{{ __('Billing Settings') }}</h2>
                                <p class="mt-1 text-sm text-gray-600">{{ __("Configure billing and disconnection schedules.") }}</p>
                            </div>

                            <div>
                                <div>
                                    <x-input-label for="bill_at" value="{{ __('Bill at day of month') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="bill_at" name="bill_at" type="number" min="1" max="31" class="mt-1 block w-full" value="{{ old('bill_at', $settings->bill_at ?? 1) }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('bill_at')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="disconnect_at" value="{{ __('Disconnect at day of month') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="disconnect_at" name="disconnect_at" type="number" min="1" max="31" class="mt-1 block w-full" value="{{ old('disconnect_at', $settings->disconnect_at ?? 5) }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('disconnect_at')"></x-input-error>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 mt-6">
                            <x-primary-button>{{ __('Save Settings') }}</x-primary-button>
                        </div>

                        <hr class="my-6">

                        <!-- SMS/Notification Settings Section -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h2 class="text-lg font-medium text-gray-900">{{ __('SMS/Notification Settings') }}</h2>
                                <p class="mt-1 text-sm text-gray-600">{{ __("Configure SMS and notification preferences.") }}</p>
                            </div>

                            <div>
                                <div>
                                    <x-input-label for="sms_api_key" value="{{ __('SMS API Key') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="sms_api_key" name="sms_api_key" type="text" class="mt-1 block w-full" value="{{ old('sms_api_key', $settings->sms_api_key ?? '') }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('sms_api_key')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="sms_sender_id" value="{{ __('SMS Sender ID') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="sms_sender_id" name="sms_sender_id" type="text" class="mt-1 block w-full" value="{{ old('sms_sender_id', $settings->sms_sender_id ?? '') }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('sms_sender_id')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="billing_alerts_enabled" value="{{ __('Enable Billing Alerts') }}" class="mt-4"></x-input-label>
                                    <select id="billing_alerts_enabled" name="billing_alerts_enabled" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                        <option value="1" {{ old('billing_alerts_enabled', $settings->billing_alerts_enabled ?? true) ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ !old('billing_alerts_enabled', $settings->billing_alerts_enabled ?? true) ? 'selected' : '' }}>No</option>
                                    </select>
                                    <x-input-error class="mt-2" :messages="$errors->get('billing_alerts_enabled')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="expiry_warnings_enabled" value="{{ __('Enable Expiry Warnings') }}" class="mt-4"></x-input-label>
                                    <select id="expiry_warnings_enabled" name="expiry_warnings_enabled" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                        <option value="1" {{ old('expiry_warnings_enabled', $settings->expiry_warnings_enabled ?? true) ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ !old('expiry_warnings_enabled', $settings->expiry_warnings_enabled ?? true) ? 'selected' : '' }}>No</option>
                                    </select>
                                    <x-input-error class="mt-2" :messages="$errors->get('expiry_warnings_enabled')"></x-input-error>
                                </div>
                            </div>
                        </div>

                        <hr class="my-6">

                        <!-- Payment Gateway Settings Section -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h2 class="text-lg font-medium text-gray-900">{{ __('Payment Gateway Settings') }}</h2>
                                <p class="mt-1 text-sm text-gray-600">{{ __("Configure payment gateway credentials.") }}</p>
                            </div>

                            <div>
                                <div>
                                    <x-input-label for="mpesa_api_key" value="{{ __('M-Pesa API Key') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="mpesa_api_key" name="mpesa_api_key" type="text" class="mt-1 block w-full" value="{{ old('mpesa_api_key', $settings->mpesa_api_key ?? '') }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('mpesa_api_key')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="mpesa_shortcode" value="{{ __('M-Pesa Shortcode') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="mpesa_shortcode" name="mpesa_shortcode" type="text" class="mt-1 block w-full" value="{{ old('mpesa_shortcode', $settings->mpesa_shortcode ?? '') }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('mpesa_shortcode')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="mpesa_passkey" value="{{ __('M-Pesa Passkey') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="mpesa_passkey" name="mpesa_passkey" type="password" class="mt-1 block w-full" value="{{ old('mpesa_passkey', $settings->mpesa_passkey ?? '') }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('mpesa_passkey')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="payment_timeout_minutes" value="{{ __('Payment Timeout (minutes)') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="payment_timeout_minutes" name="payment_timeout_minutes" type="number" min="1" class="mt-1 block w-full" value="{{ old('payment_timeout_minutes', $settings->payment_timeout_minutes ?? 15) }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('payment_timeout_minutes')"></x-input-error>
                                </div>
                            </div>
                        </div>

                        <hr class="my-6">

                        <!-- Email Settings Section -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h2 class="text-lg font-medium text-gray-900">{{ __('Email Settings') }}</h2>
                                <p class="mt-1 text-sm text-gray-600">{{ __("Configure SMTP email settings.") }}</p>
                            </div>

                            <div>
                                <div>
                                    <x-input-label for="smtp_host" value="{{ __('SMTP Host') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="smtp_host" name="smtp_host" type="text" class="mt-1 block w-full" value="{{ old('smtp_host', $settings->smtp_host ?? '') }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('smtp_host')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="smtp_port" value="{{ __('SMTP Port') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="smtp_port" name="smtp_port" type="number" class="mt-1 block w-full" value="{{ old('smtp_port', $settings->smtp_port ?? '') }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('smtp_port')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="smtp_username" value="{{ __('SMTP Username') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="smtp_username" name="smtp_username" type="text" class="mt-1 block w-full" value="{{ old('smtp_username', $settings->smtp_username ?? '') }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('smtp_username')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="smtp_password" value="{{ __('SMTP Password') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="smtp_password" name="smtp_password" type="password" class="mt-1 block w-full" value="{{ old('smtp_password', $settings->smtp_password ?? '') }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('smtp_password')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="email_from_address" value="{{ __('Email From Address') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="email_from_address" name="email_from_address" type="email" class="mt-1 block w-full" value="{{ old('email_from_address', $settings->email_from_address ?? '') }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('email_from_address')"></x-input-error>
                                </div>
                            </div>
                        </div>

                        <hr class="my-6">

                        <!-- Security Settings Section -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h2 class="text-lg font-medium text-gray-900">{{ __('Security Settings') }}</h2>
                                <p class="mt-1 text-sm text-gray-600">{{ __("Configure security and password policies.") }}</p>
                            </div>

                            <div>
                                <div>
                                    <x-input-label for="password_min_length" value="{{ __('Password Min Length') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="password_min_length" name="password_min_length" type="number" min="6" class="mt-1 block w-full" value="{{ old('password_min_length', $settings->password_min_length ?? 8) }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('password_min_length')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="password_require_uppercase" value="{{ __('Require Uppercase') }}" class="mt-4"></x-input-label>
                                    <select id="password_require_uppercase" name="password_require_uppercase" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                        <option value="1" {{ old('password_require_uppercase', $settings->password_require_uppercase ?? true) ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ !old('password_require_uppercase', $settings->password_require_uppercase ?? true) ? 'selected' : '' }}>No</option>
                                    </select>
                                    <x-input-error class="mt-2" :messages="$errors->get('password_require_uppercase')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="password_require_number" value="{{ __('Require Number') }}" class="mt-4"></x-input-label>
                                    <select id="password_require_number" name="password_require_number" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                        <option value="1" {{ old('password_require_number', $settings->password_require_number ?? true) ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ !old('password_require_number', $settings->password_require_number ?? true) ? 'selected' : '' }}>No</option>
                                    </select>
                                    <x-input-error class="mt-2" :messages="$errors->get('password_require_number')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="session_timeout_minutes" value="{{ __('Session Timeout (minutes)') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="session_timeout_minutes" name="session_timeout_minutes" type="number" min="5" class="mt-1 block w-full" value="{{ old('session_timeout_minutes', $settings->session_timeout_minutes ?? 60) }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('session_timeout_minutes')"></x-input-error>
                                </div>
                            </div>
                        </div>

                        <hr class="my-6">

                        <!-- Support/Contact Settings Section -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h2 class="text-lg font-medium text-gray-900">{{ __('Support/Contact Settings') }}</h2>
                                <p class="mt-1 text-sm text-gray-600">{{ __("Configure support contact information.") }}</p>
                            </div>

                            <div>
                                <div>
                                    <x-input-label for="support_phone" value="{{ __('Support Phone') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="support_phone" name="support_phone" type="text" class="mt-1 block w-full" value="{{ old('support_phone', $settings->support_phone ?? '') }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('support_phone')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="support_email" value="{{ __('Support Email') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="support_email" name="support_email" type="email" class="mt-1 block w-full" value="{{ old('support_email', $settings->support_email ?? '') }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('support_email')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="business_hours" value="{{ __('Business Hours') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="business_hours" name="business_hours" type="text" class="mt-1 block w-full" value="{{ old('business_hours', $settings->business_hours ?? '') }}" placeholder="Mon-Fri 9AM-5PM"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('business_hours')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="emergency_contact" value="{{ __('Emergency Contact') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="emergency_contact" name="emergency_contact" type="text" class="mt-1 block w-full" value="{{ old('emergency_contact', $settings->emergency_contact ?? '') }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('emergency_contact')"></x-input-error>
                                </div>
                            </div>
                        </div>

                        <hr class="my-6">

                        <!-- Free Package Settings Section -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h2 class="text-lg font-medium text-gray-900">{{ __('Free Package Settings') }}</h2>
                                <p class="mt-1 text-sm text-gray-600">{{ __("Configure free package limits and anti-abuse.") }}</p>
                            </div>

                            <div>
                                <div>
                                    <x-input-label for="free_package_data_limit_mb" value="{{ __('Free Package Data Limit (MB)') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="free_package_data_limit_mb" name="free_package_data_limit_mb" type="number" min="0" class="mt-1 block w-full" value="{{ old('free_package_data_limit_mb', $settings->free_package_data_limit_mb ?? 500) }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('free_package_data_limit_mb')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="free_package_validity_hours" value="{{ __('Free Package Validity (hours)') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="free_package_validity_hours" name="free_package_validity_hours" type="number" min="1" class="mt-1 block w-full" value="{{ old('free_package_validity_hours', $settings->free_package_validity_hours ?? 24) }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('free_package_validity_hours')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="anti_abuse_enabled" value="{{ __('Enable Anti-Abuse Protection') }}" class="mt-4"></x-input-label>
                                    <select id="anti_abuse_enabled" name="anti_abuse_enabled" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                        <option value="1" {{ old('anti_abuse_enabled', $settings->anti_abuse_enabled ?? true) ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ !old('anti_abuse_enabled', $settings->anti_abuse_enabled ?? true) ? 'selected' : '' }}>No</option>
                                    </select>
                                    <x-input-error class="mt-2" :messages="$errors->get('anti_abuse_enabled')"></x-input-error>
                                </div>
                            </div>
                        </div>

                        <hr class="my-6">

                        <!-- Invoice Settings Section -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h2 class="text-lg font-medium text-gray-900">{{ __('Invoice Settings') }}</h2>
                                <p class="mt-1 text-sm text-gray-600">{{ __("Configure invoice generation and formatting.") }}</p>
                            </div>

                            <div>
                                <div>
                                    <x-input-label for="invoice_prefix" value="{{ __('Invoice Prefix') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="invoice_prefix" name="invoice_prefix" type="text" class="mt-1 block w-full" value="{{ old('invoice_prefix', $settings->invoice_prefix ?? 'INV-') }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('invoice_prefix')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="payment_terms" value="{{ __('Payment Terms') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="payment_terms" name="payment_terms" type="text" class="mt-1 block w-full" value="{{ old('payment_terms', $settings->payment_terms ?? 'Net 30') }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('payment_terms')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="invoice_footer" value="{{ __('Invoice Footer') }}" class="mt-4"></x-input-label>
                                    <textarea id="invoice_footer" name="invoice_footer" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" rows="3">{{ old('invoice_footer', $settings->invoice_footer ?? '') }}</textarea>
                                    <x-input-error class="mt-2" :messages="$errors->get('invoice_footer')"></x-input-error>
                                </div>
                            </div>
                        </div>

                        <hr class="my-6">

                        <!-- Branding Settings Section -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h2 class="text-lg font-medium text-gray-900">{{ __('Branding Settings') }}</h2>
                                <p class="mt-1 text-sm text-gray-600">{{ __("Customize application branding and appearance.") }}</p>
                            </div>

                            <div>
                                <div>
                                    <x-input-label for="company_logo" value="{{ __('Company Logo URL') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="company_logo" name="company_logo" type="text" class="mt-1 block w-full" value="{{ old('company_logo', $settings->company_logo ?? '') }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('company_logo')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="favicon" value="{{ __('Favicon URL') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="favicon" name="favicon" type="text" class="mt-1 block w-full" value="{{ old('favicon', $settings->favicon ?? '') }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('favicon')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="custom_css" value="{{ __('Custom CSS') }}" class="mt-4"></x-input-label>
                                    <textarea id="custom_css" name="custom_css" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" rows="5">{{ old('custom_css', $settings->custom_css ?? '') }}</textarea>
                                    <x-input-error class="mt-2" :messages="$errors->get('custom_css')"></x-input-error>
                                </div>
                            </div>
                        </div>

                        <hr class="my-6">

                        <!-- Backup Settings Section -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h2 class="text-lg font-medium text-gray-900">{{ __('Backup Settings') }}</h2>
                                <p class="mt-1 text-sm text-gray-600">{{ __("Configure database backup schedule.") }}</p>
                            </div>

                            <div>
                                <div>
                                    <x-input-label for="backup_schedule" value="{{ __('Backup Schedule') }}" class="mt-4"></x-input-label>
                                    <select id="backup_schedule" name="backup_schedule" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                        <option value="daily" {{ old('backup_schedule', $settings->backup_schedule ?? 'daily') === 'daily' ? 'selected' : '' }}>Daily</option>
                                        <option value="weekly" {{ old('backup_schedule', $settings->backup_schedule ?? 'daily') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                        <option value="monthly" {{ old('backup_schedule', $settings->backup_schedule ?? 'daily') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                    </select>
                                    <x-input-error class="mt-2" :messages="$errors->get('backup_schedule')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="backup_retention_days" value="{{ __('Backup Retention (days)') }}" class="mt-4"></x-input-label>
                                    <x-text-input id="backup_retention_days" name="backup_retention_days" type="number" min="1" class="mt-1 block w-full" value="{{ old('backup_retention_days', $settings->backup_retention_days ?? 30) }}"></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('backup_retention_days')"></x-input-error>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 mt-6">
                            <x-primary-button>{{ __('Save Settings') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
