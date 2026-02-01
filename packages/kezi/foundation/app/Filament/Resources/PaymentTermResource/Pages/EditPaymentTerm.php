<?php

namespace Kezi\Foundation\Filament\Resources\PaymentTermResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Kezi\Foundation\Filament\Actions\DocsAction;
use Kezi\Foundation\Filament\Resources\PaymentTermResource;

class EditPaymentTerm extends EditRecord
{
    protected static string $resource = PaymentTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            DocsAction::make('payment-terms-guide'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
