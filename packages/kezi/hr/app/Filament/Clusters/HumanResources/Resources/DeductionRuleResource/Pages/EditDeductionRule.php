<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\DeductionRuleResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\DeductionRuleResource;

class EditDeductionRule extends EditRecord
{
    protected static string $resource = DeductionRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            \Kezi\Foundation\Filament\Actions\DocsAction::make('deduction-rules'),
        ];
    }
}
