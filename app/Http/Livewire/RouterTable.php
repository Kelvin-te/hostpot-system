<?php

namespace App\Http\Livewire;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Router;
use Carbon\Carbon;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class RouterTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setAdditionalSelects([
                'routers.id as id',
                'routers.hotspot_enabled as hotspot_enabled',
                'routers.is_active as is_active',
            ]);
    }

    public function builder(): Builder
    {
        return Router::query()
            ->selectRaw('routers.*, (
                SELECT COUNT(*)
                FROM hotspot_sessions
                INNER JOIN packages ON packages.id = hotspot_sessions.package_id
                WHERE packages.router_id = routers.id
                AND hotspot_sessions.status = "active"
            ) as active_sessions_count');
    }

    public function columns(): array
    {
        return [
            Column::make("Name", "name")
                ->sortable()
                ->searchable(),
            Column::make("Location", "location")
                ->sortable()
                ->searchable(),
            Column::make("IP Address", "ip")
                ->sortable()
                ->searchable(),
            Column::make("Hotspot", "hotspot_enabled")
                ->sortable()
                ->format(function ($value) {
                    return $value ? '✓' : '✗';
                }),
            Column::make("Active", "is_active")
                ->sortable()
                ->format(function ($value) {
                    return $value
                        ? '<span class="text-green-600 font-semibold">Active</span>'
                        : '<span class="text-red-600 font-semibold">Inactive</span>';
                })
                ->html(),
            Column::make("Sessions")
                ->label(fn($row) => '<span class="router-sessions" data-count="' . $row->active_sessions_count . '">' . $row->active_sessions_count . '</span>')
                ->html(),
            Column::make("RX/TX")
                ->label(fn($row) => '<span class="router-traffic text-xs text-gray-400" data-router-id="' . $row->id . '">—</span>')
                ->html(),
            Column::make("CPU")
                ->label(fn($row) => '<span class="router-cpu text-xs text-gray-400" data-router-id="' . $row->id . '">—</span>')
                ->html(),
            Column::make("Uptime")
                ->label(fn($row) => '<span class="router-uptime text-xs text-gray-400" data-router-id="' . $row->id . '">—</span>')
                ->html(),
            Column::make("Synced", "packages_sync_count")
                ->sortable(),
            Column::make("Last Synced", "last_synced_at")
                ->sortable()
                ->format(function ($value) {
                    return $value ? Carbon::parse($value)->format('M d, H:i') : 'Never';
                }),

            Column::make("Actions", "id")
                ->format(function ($value, $row) {
                    return '<a href="' . route('router.show', $row->id) . '" class="text-blue-600 hover:text-blue-800">View</a>';
                })
                ->html(),
        ];
    }
}
