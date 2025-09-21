<?php

namespace App\Http\Livewire;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Router;
use Carbon\Carbon;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class RouterTable extends DataTableComponent
{
    protected $model = Router::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->sortable(),
            Column::make("Name", "name")
                ->sortable(),
            Column::make("Location", "location")
                ->sortable(),
            Column::make("Ip", "ip")
                ->sortable(),
            Column::make("Username", "username")
                ->sortable(),
            Column::make("Password", "password")
                ->sortable(),
            Column::make("Created at", "created_at")
                ->format(function ($value) {
                    return Carbon::parse($value)->format('Y-m-d');
                })
                ->sortable(),
            Column::make("Updated at", "updated_at")
                ->format(function ($value) {
                return Carbon::parse($value)->format("Y-m-d");
                })
                ->sortable(),
            LinkColumn::make('View')
                ->title(fn($row) => 'View')
                ->location(fn($row) => route('router.show', $row)),
            LinkColumn::make('Action')
                ->title(fn($row) => 'Edit')
                ->location(fn($row) => route('router.edit', $row)),
            LinkColumn::make('View log')
                ->title(fn($row) => 'Logs')
                ->location(fn($row) => route('log', ['param' => $row])),
        ];
    }
}
