<x-app-layout>
    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:bg-slate-800 dark:border-slate-700">
                <div class="p-6 text-slate-900 dark:text-slate-100">
                    @if(session('success'))
                        <div class="alert alert-success text-green-600">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger text-red-600">{{ session('error') }}</div>
                    @endif

                    <div class="flex justify-between items-center mb-6 border-b-2 border-slate-100 pb-4">
                        <h2 class="font-semibold text-xl">{{ __('Resellers / Landlords') }}</h2>
                        <a href="{{ route('resellers.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">{{ __('Add Reseller') }}</a>
                    </div>

                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left">{{ __('Business') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('Contact') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('Brand') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('M-Pesa') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('Routers') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('Packages') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('Subscribers') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('Status') }}</th>
                                <th class="px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            @forelse($resellers as $reseller)
                                <tr>
                                    <td class="px-4 py-2 font-medium">{{ $reseller->business_name }}</td>
                                    <td class="px-4 py-2">{{ $reseller->contact_name }}<br><span class="text-xs text-slate-500">{{ $reseller->phone }}</span></td>
                                    <td class="px-4 py-2">{{ $reseller->brand_name ?? '—' }}</td>
                                    <td class="px-4 py-2">{{ !empty($reseller->mpesa_credentials) ? __('Own till') : __('ISP till') }}</td>
                                    <td class="px-4 py-2">{{ $reseller->routers_count }}</td>
                                    <td class="px-4 py-2">{{ $reseller->packages_count }}</td>
                                    <td class="px-4 py-2">{{ $reseller->users_count }}</td>
                                    <td class="px-4 py-2">
                                        <span class="px-2 py-1 rounded text-xs {{ $reseller->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ ucfirst($reseller->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <a href="{{ route('resellers.edit', $reseller) }}" class="text-blue-600 hover:underline text-sm">{{ __('Edit') }}</a>
                                        <form method="post" action="{{ route('resellers.toggle', $reseller) }}" class="inline ml-2">@csrf<button class="text-yellow-600 hover:underline text-sm">{{ $reseller->status === 'active' ? __('Suspend') : __('Activate') }}</button></form>
                                        <form method="post" action="{{ route('resellers.destroy', $reseller) }}" class="inline ml-2" onsubmit="return confirm('{{ __('Delete this reseller?') }}')">@csrf @method('DELETE')<button class="text-red-600 hover:underline text-sm">{{ __('Delete') }}</button></form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="px-4 py-6 text-center text-slate-500">{{ __('No resellers yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
