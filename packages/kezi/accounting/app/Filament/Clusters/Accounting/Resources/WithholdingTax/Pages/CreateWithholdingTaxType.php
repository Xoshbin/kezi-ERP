<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\WithholdingTax\Pages;

use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\WithholdingTax\WithholdingTaxTypeResource;

class CreateWithholdingTaxType extends CreateRecord
{
    use Translatable;

    protected static string $resource = WithholdingTaxTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $action = app(\Kezi\Accounting\Actions\Accounting\CreateWithholdingTaxTypeAction::class);

        $dto = new \Kezi\Accounting\DataTransferObjects\Accounting\CreateWithholdingTaxTypeDTO(
            company_id: filament()->getTenant()->id,
            name: is_array($data['name']) ? $data['name'] : [app()->getLocale() => $data['name']],
            rate: $data['rate'] / 100, // Convert percentage to decimal
            withholding_account_id: $data['withholding_account_id'],
            applicable_to: \Kezi\Accounting\Enums\Accounting\WithholdingTaxApplicability::from($data['applicable_to']),
            threshold_amount: $data['threshold_amount'] ?? null,
            is_active: $data['is_active'],
        );

        return $action->execute($dto);
    }
}
