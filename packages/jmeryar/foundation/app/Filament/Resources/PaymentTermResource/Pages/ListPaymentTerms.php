<?php

namespace Jmeryar\Foundation\Filament\Resources\PaymentTermResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Jmeryar\Foundation\Filament\Actions\DocsAction;
use Jmeryar\Foundation\Filament\Resources\PaymentTermResource;

class ListPaymentTerms extends ListRecords
{
    protected static string $resource = PaymentTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            DocsAction::make('payment-terms-guide'),
            DocsAction::make('incoterms'),
        ];
    }
}
