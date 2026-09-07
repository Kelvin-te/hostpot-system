<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
                    @endif

                    <div class="flex justify-between items-center mb-6 border-b-2 border-slate-100 pb-4">
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Copy Packages to Router</h2>
                        <a href="{{ route('packages.index') }}" class="text-gray-600 hover:text-gray-800">Back</a>
                    </div>

                    <form method="POST" action="{{ route('packages.copy') }}" class="space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="dest_router_id" :value="__('Destination Router')" />
                            <select id="dest_router_id" name="dest_router_id" class="mt-1 block w-full rounded-md border border-gray-300" required>
                                <option value="">Select destination router</option>
                                @foreach ($routers as $r)
                                    <option value="{{ $r->id }}">{{ $r->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('dest_router_id')" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Packages to Copy</label>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm border border-gray-200">
                                    <thead>
                                        <tr class="bg-gray-50 border-b">
                                            <th class="py-2 px-3 text-left w-10">
                                                <input type="checkbox" id="select_all" class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                            </th>
                                            <th class="py-2 px-3 text-left">Package Name</th>
                                            <th class="py-2 px-3 text-left">Current Router</th>
                                            <th class="py-2 px-3 text-right">Price</th>
                                            <th class="py-2 px-3 text-left">TX/RX (Mbps)</th>
                                            <th class="py-2 px-3 text-left">Devices</th>
                                            <th class="py-2 px-3 text-left">Validity</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($packages as $pkg)
                                            <tr class="border-b hover:bg-gray-50">
                                                <td class="py-2 px-3">
                                                    <input type="checkbox" name="package_ids[]" value="{{ $pkg->id }}" class="h-4 w-4 text-indigo-600 border-gray-300 rounded package-checkbox">
                                                </td>
                                                <td class="py-2 px-3 font-medium">{{ $pkg->name }}</td>
                                                <td class="py-2 px-3 text-gray-600">{{ $pkg->router?->name ?: '—' }}</td>
                                                <td class="py-2 px-3 text-right">
                                                    @if($pkg->price == 0)
                                                        <span class="text-green-600 font-semibold">FREE</span>
                                                    @else
                                                        {{ config('app.currency', 'KSh') }} {{ number_format($pkg->price, 0) }}
                                                    @endif
                                                </td>
                                                <td class="py-2 px-3 text-gray-600">
                                                    @if($pkg->bandwidth_upload || $pkg->bandwidth_download)
                                                        {{ $pkg->bandwidth_upload ?: '—' }} / {{ $pkg->bandwidth_download ?: '—' }}
                                                    @else
                                                        Unlimited
                                                    @endif
                                                </td>
                                                <td class="py-2 px-3 text-gray-600">{{ $pkg->shared_users ?: 1 }}</td>
                                                <td class="py-2 px-3 text-gray-600">
                                                    @if($pkg->validity_days)
                                                        {{ $pkg->validity_days }} day(s)
                                                    @elseif($pkg->validity_minutes)
                                                        {{ $pkg->validity_minutes }} min
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        @if($packages->isEmpty())
                                            <tr>
                                                <td colspan="7" class="py-4 text-center text-gray-400">No packages available.</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            <x-input-error class="mt-2" :messages="$errors->get('package_ids')" />
                        </div>

                        <div class="flex items-center">
                            <input id="overwrite" name="overwrite" type="checkbox" value="1" class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                            <label for="overwrite" class="ml-2 block text-sm text-gray-700">Overwrite existing packages with same name on destination</label>
                        </div>

                        <div class="flex items-center gap-4 mt-6">
                            <x-primary-button>Copy Selected Packages</x-primary-button>
                            <a href="{{ route('packages.index') }}" class="text-gray-600 hover:text-gray-800">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('select_all');
            const checkboxes = document.querySelectorAll('.package-checkbox');

            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = selectAll.checked);
            });

            checkboxes.forEach(cb => {
                cb.addEventListener('change', function() {
                    const allChecked = Array.from(checkboxes).every(c => c.checked);
                    selectAll.checked = allChecked;
                });
            });
        });
    </script>
    @endpush
</x-app-layout>
