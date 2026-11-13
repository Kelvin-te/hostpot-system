<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="p-6 text-slate-900">
                    <div class="flex justify-between items-center mb-6 border-b-2 border-slate-100 pb-4">
                        <h2 class="text-xl font-semibold text-slate-900">Vouchers</h2>
                        <div class="flex space-x-2">
                            <a href="{{ route('vouchers.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">Create Vouchers</a>
                            <a href="{{ route('vouchers.export') }}" class="inline-flex items-center px-4 py-2 bg-slate-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition ease-in-out duration-150">Export CSV</a>
                        </div>
                    </div>

                    @if(session('success'))
                        <div class="mb-4 px-4 py-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="mb-4 px-4 py-3 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Package</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Expires</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Used At</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Created</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-200">
                                @forelse($vouchers as $v)
                                    <tr class="{{ $loop->even ? 'bg-white' : 'bg-slate-50' }}">
                                        <td class="px-6 py-4 text-sm font-mono">{{ $v->code }}</td>
                                        <td class="px-6 py-4 text-sm">{{ optional($v->package)->name }}</td>
                                        <td class="px-6 py-4 text-sm">
                                            <span class="px-2 py-1 rounded text-white {{ $v->status === 'active' ? 'bg-green-600' : ($v->status === 'used' ? 'bg-slate-600' : 'bg-red-600') }}">{{ ucfirst($v->status) }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm">{{ optional($v->expires_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                        <td class="px-6 py-4 text-sm">{{ optional($v->used_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                        <td class="px-6 py-4 text-sm">{{ optional($v->created_at)->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="px-6 py-6 text-center text-slate-500">No vouchers found.</td></tr>
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
