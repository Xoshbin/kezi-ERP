<?php

namespace App\Filament\Resources\Companies\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Companies\CompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
