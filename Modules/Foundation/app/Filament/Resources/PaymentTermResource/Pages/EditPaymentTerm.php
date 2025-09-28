<?php

namespace Modules\Foundation\Filament\Clusters\Settings\Resources\PaymentTermResource\Pages;

use Filament\Actions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Foundation\Filament\Actions\DocsAction;
use Modules\Foundation\Filament\Clusters\Settings\Resources\PaymentTermResource;

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
