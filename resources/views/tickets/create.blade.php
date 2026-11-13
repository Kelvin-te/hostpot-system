<x-app-layout>
    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="p-4 sm:p-8">
                    @if(session('error'))
                        <div class="alert alert-danger text-red-600">
                            {{ session('error') }}
                        </div>
                    @endif

                        <h2 class="text-xl font-semibold text-slate-900 border-b-2 border-slate-100 pb-4">
                            {{ __('Create Ticket') }}
                        </h2>

                    <form method="post" action="{{ route('ticket.store') }}" class="space-y-6">
                        @csrf

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h2 class="text-base font-semibold text-slate-900">{{ __('Ticket') }}</h2>
                                <p class="mt-1 text-slate-600">{{ __("Create a new ticket") }}</p>
                            </div>

                            <div>
                                <div>
                                    <x-input-label for="subject" :value="__('Subject')"></x-input-label>
                                    <x-text-input id="subject" name="subject" type="text" class="mt-1 block w-full" :value="old('subject')" required></x-text-input>
                                    <x-input-error class="mt-2" :messages="$errors->get('subject')"></x-input-error>
                                </div>
                                <div>
                                    <x-input-label for="message" :value="__('Message')" class="mt-4"></x-input-label>
                                    <textarea name="message" class="px-3 py-3 mt-1 rounded-lg border-slate-300 md: block w-full" rows="5"></textarea>
                                    <x-input-error class="mt-2" :messages="$errors->get('message')"></x-input-error>
                                </div>

                                <div>
                                    <x-input-label for="priority" :value="__('Priority')" class="mt-4"></x-input-label>
                                    <x-select name="priority" id="priority" class="mt-1 block w-full rounded-md border border-slate-300">
                                        <option value="High">{{ __('High') }}</option>
                                        <option value="Normal">{{ __('Normal') }}</option>
                                        <option value="Low">{{ __('Low') }}</option>
                                    </x-select>
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
