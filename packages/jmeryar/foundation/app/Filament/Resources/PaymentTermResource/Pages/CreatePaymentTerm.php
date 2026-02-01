<?php

namespace Jmeryar\Foundation\Filament\Resources\PaymentTermResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Jmeryar\Foundation\Filament\Actions\DocsAction;
use Jmeryar\Foundation\Filament\Resources\PaymentTermResource;

class CreatePaymentTerm extends CreateRecord
{
    protected static string $resource = PaymentTermResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('payment-terms-guide'),
        ];
    }
}
