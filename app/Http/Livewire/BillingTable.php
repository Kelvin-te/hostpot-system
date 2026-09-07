<?php

namespace App\Http\Livewire;

use App\Models\PaymentTransaction;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class BillingTable extends DataTableComponent
{
    protected $model = PaymentTransaction::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setAdditionalSelects(['payment_transactions.id as id']);
    }

    public function builder(): Builder
    {
        return PaymentTransaction::query()
            ->with(['user', 'package', 'router'])
            ->where('payment_transactions.status', 'completed');
    }

    public function columns(): array
    {
        return [
            Column::make("Receipt", "mpesa_receipt_number")
                ->sortable()
                ->searchable()
                ->format(fn($value) => $value ?: '—'),
            Column::make("Customer", "user.name")
                ->sortable()
                ->searchable()
                ->format(fn($value, $row) => $value ?: $row->phone_number),
            Column::make("Phone", "phone_number")
                ->sortable()
                ->searchable(),
            Column::make("Package", "package.name")
                ->sortable()
                ->searchable(),
            Column::make("Amount", "amount")
                ->sortable()
                ->searchable()
                ->format(fn($value) => number_format((float)$value, 2)),
            Column::make("Gateway", "gateway")
                ->sortable(),
            Column::make("Type", "type")
                ->sortable(),
            Column::make("Date", "created_at")
                ->sortable()
                ->format(fn($value) => \Carbon\Carbon::parse($value)->format('Y-m-d H:i'))
                ->html(),
        ];
    }
}
