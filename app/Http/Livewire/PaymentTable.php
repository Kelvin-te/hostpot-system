<?php

namespace App\Http\Livewire;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\PaymentTransaction;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class PaymentTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder
    {
        return PaymentTransaction::query()->with(['user', 'package', 'router']);
    }

    public function columns(): array
    {
        return [
            Column::make("Receipt", "mpesa_receipt_number")
                ->sortable()
                ->searchable()
                ->format(fn($value) => $value ?: '—'),
            Column::make("Phone", "phone_number")
                ->sortable()
                ->searchable(),
            Column::make("Package", "package_id")
                ->sortable()
                ->format(fn($value, $row) => $row->package?->name ?? '—'),
            Column::make("Amount", "amount")
                ->sortable()
                ->searchable()
                ->format(fn($value) => number_format((float)$value, 2)),
            Column::make("Gateway", "gateway")
                ->sortable()
                ->searchable(),
            Column::make("Type", "type")
                ->sortable(),
            Column::make("Status", "status")
                ->sortable()
                ->format(fn($value) => match($value) {
                    'completed' => '<span class="text-green-600">✓ Completed</span>',
                    'pending' => '<span class="text-yellow-600">⏳ Pending</span>',
                    'failed' => '<span class="text-red-600">✗ Failed</span>',
                    'expired' => '<span class="text-gray-500">⊘ Expired</span>',
                    default => $value,
                })
                ->html(),
            Column::make("Date", "created_at")
                ->format(function ($value) {
                    return Carbon::parse($value)->format('Y-m-d H:i');
                })
                ->html(),
            LinkColumn::make('Action')
                ->title(fn($row) => 'Download')
                ->location(fn($row) => route('invoice.download', ['row' => $row->id])),
        ];
    }
}
