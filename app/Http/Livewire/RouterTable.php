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
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder
    {
        return Router::query();
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
                ->format(function ($value) {
                    return $value ? '✓' : '✗';
                }),
            Column::make("Synced", "packages_sync_count")
                ->sortable(),
            Column::make("Last Synced", "last_synced_at")
                ->format(function ($value) {
                    return $value ? \Carbon\Carbon::parse($value)->format('M d, H:i') : 'Never';
                }),

            Column::make("Actions", "id")
                ->format(function ($value, $row) {
                    return '<a href="' . route('router.show', $row->id) . '" class="text-blue-600 hover:text-blue-800">View</a>';
                })
                ->html(),
        ];
    }
}
