<?php

namespace App\Http\Livewire;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Payment;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class PaymentTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder
    {
        return Payment::query()->with(['user', 'billing']);
    }

    public function columns(): array
    {
        return [
            Column::make("Invoice", "invoice")
                ->sortable()
                ->searchable(),
            Column::make("User", "user.name")
                ->sortable()
                ->searchable(),
            Column::make("Package", "billing.package_name")
                ->sortable()
                ->searchable(),
            Column::make("Price" . __(' (') . config('app.currency') . __(')'), "package_price")
                ->sortable()
                ->searchable(),
            Column::make("Method", "payment_method")
                ->sortable(),
            Column::make("Date", "created_at")
                ->format(function ($value) {
                    return Carbon::parse($value)->format('Y-m-d');
                })
                ->html(),
            LinkColumn::make('Action')
                ->title(fn($row) => 'Download')
                ->location(fn($row) => route('invoice.download', $row->invoice)),
        ];
    }
}
