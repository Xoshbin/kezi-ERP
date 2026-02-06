<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\WithholdingTax\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\WithholdingTax\WithholdingTaxTypeResource;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

/**
 * @extends EditRecord<\Kezi\Accounting\Models\WithholdingTaxType>
 */
class EditWithholdingTaxType extends EditRecord
{
    use Translatable;

    protected static string $resource = WithholdingTaxTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($data['rate'])) {
            $data['rate'] = $data['rate'] * 100; // Convert decimal to percentage for display
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['rate'])) {
            $data['rate'] = $data['rate'] / 100; // Convert percentage to decimal for storage
        }

        return $data;
    }

    public function getTitle(): string
    {
        return __('accounting::withholding_tax.pages.edit');
    }
}
