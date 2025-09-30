<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-6 border-b-2 border-slate-100 pb-4">
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Vouchers</h2>
                        <div class="flex space-x-2">
                            <a href="{{ route('vouchers.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">Create Vouchers</a>
                            <a href="{{ route('vouchers.export') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">Export CSV</a>
                        </div>
                    </div>

                    @if(session('success'))
                        <div class="mb-4 px-4 py-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="mb-4 px-4 py-3 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm border">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left">Code</th>
                                    <th class="px-3 py-2 text-left">Package</th>
                                    <th class="px-3 py-2 text-left">Status</th>
                                    <th class="px-3 py-2 text-left">Expires</th>
                                    <th class="px-3 py-2 text-left">Used At</th>
                                    <th class="px-3 py-2 text-left">Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($vouchers as $v)
                                    <tr class="border-b">
                                        <td class="px-3 py-2 font-mono">{{ $v->code }}</td>
                                        <td class="px-3 py-2">{{ optional($v->package)->name }}</td>
                                        <td class="px-3 py-2">
                                            <span class="px-2 py-1 rounded text-white {{ $v->status === 'active' ? 'bg-green-600' : ($v->status === 'used' ? 'bg-gray-600' : 'bg-red-600') }}">{{ ucfirst($v->status) }}</span>
                                        </td>
                                        <td class="px-3 py-2">{{ optional($v->expires_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                        <td class="px-3 py-2">{{ optional($v->used_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                        <td class="px-3 py-2">{{ optional($v->created_at)->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="px-3 py-6 text-center text-gray-500">No vouchers found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $vouchers->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
