<?php

namespace App\Http\Livewire;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use App\Models\User;
use App\Models\Router;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class CustomerTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setAdditionalSelects([
                'users.id as id',
                'users.status as status',
                'users.total_spent as total_spent',
                'users.total_sessions as total_sessions',
                'users.last_session_at as last_session_at',
            ]);
    }

    public function builder(): Builder
    {
        return User::query()
            ->with(['router', 'sessions']);
    }

    public function filters(): array
    {
        $routerOptions = ['' => 'All Routers'];
        Router::orderBy('name')->get()->each(function ($router) use (&$routerOptions) {
            $routerOptions[$router->id] = $router->name;
        });

        return [
            SelectFilter::make('Router')
                ->options($routerOptions)
                ->filter(function (Builder $builder, string $value) {
                    if ($value !== '') {
                        $builder->where('users.router_id', $value);
                    }
                }),
            SelectFilter::make('Status')
                ->options([
                    '' => 'All',
                    'active' => 'Active',
                    'suspended' => 'Suspended',
                    'banned' => 'Banned',
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value !== '') {
                        $builder->where('users.status', $value);
                    }
                }),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make("Phone", "phone")
                ->sortable()
                ->searchable(),
            Column::make("Name", "name")
                ->sortable()
                ->searchable(),
            Column::make("Router", "router.name")
                ->sortable()
                ->searchable(),
            Column::make("Status", "status")
                ->sortable()
                ->format(function ($value) {
                    $colors = [
                        'active' => 'text-green-600',
                        'suspended' => 'text-amber-600',
                        'banned' => 'text-red-600',
                    ];
                    $class = $colors[$value] ?? 'text-gray-600';
                    return '<span class="' . $class . ' font-semibold capitalize">' . $value . '</span>';
                })
                ->html(),
            Column::make("Total Spent", "total_spent")
                ->sortable()
                ->format(function ($value) {
                    return config('app.currency', 'KSh') . ' ' . number_format((float) $value, 2);
                }),
            Column::make("Sessions", "total_sessions")
                ->sortable(),
            Column::make("Last Active", "last_session_at")
                ->sortable()
                ->format(function ($value) {
                    return $value ? \Carbon\Carbon::parse($value)->diffForHumans() : 'Never';
                }),
            LinkColumn::make('Actions')
                ->title(fn($row) => 'View')
                ->location(fn($row) => route('customers.show', $row)),
        ];
    }
}
