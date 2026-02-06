<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\EmploymentContracts\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kezi\Foundation\Filament\Actions\DocsAction;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\EmploymentContracts\EmploymentContractResource;

class ListEmploymentContracts extends ListRecords
{
    protected static string $resource = EmploymentContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('employment-contracts'),
            CreateAction::make(),
        ];
    }
}
