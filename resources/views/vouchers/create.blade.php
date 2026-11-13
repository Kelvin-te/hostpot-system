<x-app-layout>
    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="p-6 text-slate-900">
                    <div class="flex justify-between items-center mb-6 border-b-2 border-slate-100 pb-4">
                        <h2 class="text-xl font-semibold text-slate-900">Create Vouchers</h2>
                        <a href="{{ route('vouchers.index') }}" class="inline-flex items-center px-4 py-2 bg-slate-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition ease-in-out duration-150">Back</a>
                    </div>

                    @if ($errors->any())
                        <div class="mb-4 px-4 py-3 bg-red-100 text-red-800 rounded">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('vouchers.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block font-medium text-slate-700">Package</label>
                            <x-select name="package_id" class="mt-1 block w-full rounded border-slate-300">
                                @foreach($packages as $pkg)
                                    <option value="{{ $pkg->id }}">{{ $pkg->name }} ({{ config('app.currency') }} {{ number_format($pkg->price,0) }}) — {{ $pkg->router?->name ?? 'No router' }}</option>
                                @endforeach
                            </x-select>
                        </div>
                        <div>
                            <label class="block font-medium text-slate-700">Quantity</label>
                            <x-text-input type="number" name="quantity" min="1" max="1000" value="1" class="mt-1" />
                        </div>
                        <div>
                            <label class="block font-medium text-slate-700">Expires At (optional)</label>
                            <x-text-input type="datetime-local" name="expires_at" class="mt-1" />
                        </div>
                        <div>
                            <label class="block font-medium text-slate-700">Send to Phone (optional, for single voucher)</label>
                            <x-text-input type="tel" name="send_to" placeholder="e.g 0712345678" class="mt-1" />
                            <p class="text-xs text-slate-500 mt-1">If provided and quantity is 1, the voucher will be SMSed via Vintex API.</p>
                        </div>
                        <div class="pt-4">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">Create</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
