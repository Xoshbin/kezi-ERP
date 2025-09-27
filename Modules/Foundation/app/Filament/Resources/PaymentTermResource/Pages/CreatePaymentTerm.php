<?php

namespace Modules\Foundation\Filament\Clusters\Settings\Resources\PaymentTermResource\Pages;

use App\Filament\Actions\DocsAction;
use App\Filament\Clusters\Settings\Resources\PaymentTermResource;
use Filament\Resources\Pages\CreateRecord;

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
