<?php

namespace App\Http\Livewire;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Billing;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class UserBillingTable extends DataTableComponent
{
    protected $model = Billing::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setAdditionalSelects(['billings.id as id']);
    }

    public function columns(): array
    {
        return [
            Column::make("Invoice", "invoice")
                ->sortable()
                ->searchable(),
            Column::make("Package name", "package_name")
                ->sortable()
                ->searchable(),
            Column::make("Package price" . __(' (') . config('app.currency') . __(')'), "package_price")
                ->sortable()
                ->searchable(),
            Column::make("Package start", "package_start")
                ->sortable(),
            Column::make('Payment')
                ->label(function ($row) {
                    $query = Payment::firstWhere('billing_id', $row->id);
                    if ($query) {
                        return __('Paid');
                    } else {
                        $stripeUrlName = 'payment.create';
                        $stripeUrlText = 'Pay with Stripe';
                        $stripeParameter = $row->id;
                        $stripeUrl = route($stripeUrlName, ['param' => $stripeParameter]);
                        $stripeButton = "<a href='{$stripeUrl}' class='btn btn-danger'>{$stripeUrlText}</a>";
                        
                        // Add bKash button
                        $bkashUrlName = 'bkash-create-payment';
                        $bkashUrlText = 'Pay with bKash';
                        $bkashParameter = $row->id;
                        $bkashUrl = route($bkashUrlName, ['param' => $bkashParameter]);
                        $bkashButton = "<a href='{$bkashUrl}' class='btn btn-success'>{$bkashUrlText}</a>";

                        // Return both buttons
                        return new HtmlString($stripeButton . ' | ' . $bkashButton);
                    }
                }),
        ];
    }

    public function builder(): Builder
    {
        return Billing::query()->where('user_id', auth()->id());
    }
}
