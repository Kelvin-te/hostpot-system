<?php

namespace App\Http\Livewire;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class StaffTable extends DataTableComponent
{
    protected $model = Staff::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setTableRowUrl(fn ($row) => route('staff.edit', $row));
    }

    public function columns(): array
    {
        return [
            Column::make('Name', 'name')->sortable()->searchable(),
            Column::make('Email', 'email')->sortable()->searchable(),
            Column::make('Phone', 'phone')->sortable()->searchable(),
            Column::make('Role', 'role')->sortable(),
            Column::make('Status', 'is_active')
                ->label(fn ($row) => $row->is_active
                    ? '<span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">' . __('Active') . '</span>'
                    : '<span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">' . __('Inactive') . '</span>')
                ->html(),
        ];
    }

    public function builder(): Builder
    {
        return Staff::query();
    }
}
