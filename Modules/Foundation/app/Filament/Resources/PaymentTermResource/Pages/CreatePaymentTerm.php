<?php

namespace Modules\Foundation\Filament\Clusters\Settings\Resources\PaymentTermResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Foundation\Filament\Actions\DocsAction;
use Modules\Foundation\Filament\Clusters\Settings\Resources\PaymentTermResource;

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
