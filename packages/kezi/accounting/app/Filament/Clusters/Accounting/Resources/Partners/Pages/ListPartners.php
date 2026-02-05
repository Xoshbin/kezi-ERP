<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Partners\Pages;

use \Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Partners\PartnerResource;
use Kezi\Foundation\Filament\Actions\DocsAction;

class ListPartners extends ListRecords
{
    protected static string $resource = PartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('understanding-vendor-management'),
            CreateAction::make(),
        ];
    }
}
