<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\DeductionRuleResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\DeductionRuleResource;

class CreateDeductionRule extends CreateRecord
{
    protected static string $resource = DeductionRuleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = \Filament\Facades\Filament::getTenant()->id;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            \Kezi\Foundation\Filament\Actions\DocsAction::make('deduction-rules'),
        ];
    }
}
