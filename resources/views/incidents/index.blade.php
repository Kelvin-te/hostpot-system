<x-app-layout>
    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:bg-slate-800 dark:border-slate-700">
                <div class="p-6 text-slate-900 dark:text-slate-100">
                    @if(session('success'))
                        <div class="alert alert-success text-green-600">{{ session('success') }}</div>
                    @endif

                    <div class="flex justify-between items-center mb-6 border-b-2 border-slate-100 pb-4">
                        <h2 class="text-xl font-semibold text-slate-900">
                            {{ __('Network Incidents') }}
                        </h2>
                        <form method="get" class="flex gap-2">
                            <x-select name="status" class="rounded-md border-slate-300 dark:bg-slate-900 dark:text-slate-100" onchange="this.form.submit()">
                                <option value="">{{ __('All') }}</option>
                                @foreach(['open', 'acknowledged', 'resolved'] as $s)
                                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                @endforeach
                            </x-select>
                        </form>
                    </div>

                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('Opened') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('Incident') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('Router') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('Affected') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('Status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('Duration') }}</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            @forelse($incidents as $incident)
                                <tr class="{{ $loop->even ? 'bg-white' : 'bg-slate-50' }}">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $incident->opened_at->diffForHumans() }}</td>
                                    <td class="px-6 py-3">
                                        <div class="font-medium">{{ $incident->title }}</div>
                                        <div class="text-xs text-slate-500">{{ $incident->description }}</div>
                                    </td>
                                    <td class="px-6 py-3">{{ $incident->router?->name ?? '—' }}</td>
                                    <td class="px-6 py-3">{{ $incident->affected_subscribers }} {{ __('sessions') }}</td>
                                    <td class="px-6 py-3">
                                        <span class="px-2 py-1 rounded text-xs {{ $incident->status === 'resolved' ? 'bg-green-100 text-green-800' : ($incident->status === 'acknowledged' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800') }}">
                                            {{ ucfirst($incident->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if($incident->resolved_at)
                                            {{ $incident->opened_at->diffForHumans($incident->resolved_at, true) }}
                                        @else
                                            {{ $incident->opened_at->diffForHumans() }} →
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if($incident->status === 'open')
                                            <form method="post" action="{{ route('incidents.acknowledge', $incident) }}" class="inline">@csrf<button class="text-blue-600 hover:underline text-sm">{{ __('Ack') }}</button></form>
                                        @endif
                                        @if($incident->status !== 'resolved')
                                            <form method="post" action="{{ route('incidents.resolve', $incident) }}" class="inline ml-2">@csrf<button class="text-green-600 hover:underline text-sm">{{ __('Resolve') }}</button></form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-6 py-6 text-center text-slate-500">{{ __('No incidents recorded.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">{{ $incidents->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
