<?php

namespace App\Http\Livewire;

use Carbon\Carbon;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Package;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class PackageTable extends DataTableComponent
{
    protected $model = Package::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setAdditionalSelects(['packages.id as id'])
            ->setTableRowUrl(function($row) {
                return route('packages.show', $row);
            });
    }

    public function columns(): array
    {
        return [
            Column::make("ID", "id")
                ->sortable(),
            Column::make("Router","router.name")
                ->searchable(),
            Column::make("Package name", "name")
                ->sortable()
                ->searchable(),
            Column::make("Price" . __(' (') . config('app.currency') . __(')'), "price")
                ->sortable(),
            Column::make("Bandwidth", "bandwidth_download")
                ->format(function ($value, $row) {
                    if ($row->bandwidth_upload && $row->bandwidth_download) {
                        return $row->bandwidth_upload . '/' . $row->bandwidth_download . ' Mbps';
                    }
                    return $row->rate_limit ?: '-';
                }),
            Column::make("Session Time", "session_timeout")
                ->format(function ($value) {
                    return $value ? $value . 'h' : '-';
                }),
            Column::make("Validity", "validity_days")
                ->format(function ($value) {
                    return $value ? $value . ' days' : '-';
                }),
            Column::make("Shared Users", "shared_users")
                ->format(function ($value) {
                    return $value ?: '1';
                }),
            Column::make("Created at", "created_at")
                ->format(function ($value) {
                    return Carbon::parse($value)->format('Y-m-d');
                }),
            LinkColumn::make('Action')
                ->title(fn($row) => 'Edit')
                ->location(fn($row) => route('packages.edit', $row)),
        ];
    }
}
