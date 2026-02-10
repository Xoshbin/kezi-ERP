<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\DeductionRuleResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\DeductionRuleResource;

class ListDeductionRules extends ListRecords
{
    protected static string $resource = DeductionRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            \Kezi\Foundation\Filament\Actions\DocsAction::make('deduction-rules'),
        ];
    }
}
