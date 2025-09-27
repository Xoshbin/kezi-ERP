<?php

namespace Modules\Foundation\Filament\Clusters\Settings\Resources\PaymentTermResource\Pages;

use App\Filament\Actions\DocsAction;
use App\Filament\Clusters\Settings\Resources\PaymentTermResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentTerms extends ListRecords
{
    protected static string $resource = PaymentTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            DocsAction::make('payment-terms-guide'),
        ];
    }
}
