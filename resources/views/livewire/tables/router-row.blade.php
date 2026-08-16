<tr wire:click="$emitTo('router-table', 'rowClicked', {{ $row->id }})" class="cursor-pointer hover:bg-gray-50">
    <td>{{ $row->name }}</td>
    <td>{{ $row->location ?? '-' }}</td>
    <td class="font-mono text-sm">{{ $row->ip }}</td>
    <td>{{ $row->hotspot_enabled ? '✓' : '✗' }}</td>
    <td>{{ $row->packages_sync_count ?? 0 }}</td>
    <td>{{ $row->last_synced_at ? $row->last_synced_at->format('M d, H:i') : 'Never' }}</td>
    <td>{!! $getColumnValue('Actions', $row) !!}</td>
</tr>
