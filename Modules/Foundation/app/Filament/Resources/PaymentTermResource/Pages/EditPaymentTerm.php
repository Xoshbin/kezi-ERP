<?php

namespace Modules\Foundation\Filament\Clusters\Settings\Resources\PaymentTermResource\Pages;

use App\Filament\Actions\DocsAction;
use App\Filament\Clusters\Settings\Resources\PaymentTermResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

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
