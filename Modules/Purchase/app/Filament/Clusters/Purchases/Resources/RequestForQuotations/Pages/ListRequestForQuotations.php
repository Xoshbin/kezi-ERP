<?php

namespace Modules\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\RequestForQuotationResource;

class ListRequestForQuotations extends ListRecords
{
    protected static string $resource = RequestForQuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
