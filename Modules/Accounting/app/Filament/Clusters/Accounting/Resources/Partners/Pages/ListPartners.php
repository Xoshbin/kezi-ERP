<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Partners\Pages;

use App\Filament\Clusters\Accounting\Resources\Partners\PartnerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPartners extends ListRecords
{
    protected static string $resource = PartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
