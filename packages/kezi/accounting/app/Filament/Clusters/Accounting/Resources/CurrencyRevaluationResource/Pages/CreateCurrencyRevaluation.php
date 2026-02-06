<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\CurrencyRevaluationResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\CurrencyRevaluationResource;

/**
 * @extends CreateRecord<\Kezi\Accounting\Models\CurrencyRevaluation>
 */
class CreateCurrencyRevaluation extends CreateRecord
{
    protected static string $resource = CurrencyRevaluationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = \Filament\Facades\Filament::getTenant()?->getKey();
        $data['created_by_user_id'] = auth()->id();

        return $data;
    }
}
