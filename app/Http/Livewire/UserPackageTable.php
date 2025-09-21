<?php

namespace App\Http\Livewire;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Package;
use App\Models\Router;

class UserPackageTable extends DataTableComponent
{
    protected $model = Package::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function columns(): array
    {
        return [
            Column::make("Package ID", "id")
                ->sortable()
                ->searchable(),
                Column::make("Package name", "name")
                ->sortable()
                ->searchable(),
            Column::make("Price" . __(' (') . config('app.currency') . __(')'), "price")
                ->sortable(),
            Column::make("Created at", "created_at")
                ->format(function ($value) {
                    return Carbon::parse($value)->format('Y-m-d');
                }),
        ];
    }

    public function builder(): Builder
    {
        if (auth()->user()->isUser()) {
            $router_name = auth()->user()->detail->router_name;
            $router = Router::where("name", $router_name)->firstOrFail();
            return Package::query()->where('router_id', $router->id)->orderBy('name');
        }

        return Package::query();
    }
}
