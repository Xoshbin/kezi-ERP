<?php

namespace App\Filament\Resources\Partners\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Partners\PartnerResource;
use Filament\Actions;
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
