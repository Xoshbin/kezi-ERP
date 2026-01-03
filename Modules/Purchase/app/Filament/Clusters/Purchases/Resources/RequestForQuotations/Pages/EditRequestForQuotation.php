<?php

namespace Modules\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\RequestForQuotationResource;

class EditRequestForQuotation extends EditRecord
{
    protected static string $resource = RequestForQuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
