<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Partners\Pages;

use \Filament\Actions\DeleteAction;
use \Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Partners\PartnerResource;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Partners\Widgets\VendorFinancialWidget;

/**
 * @extends EditRecord<\Kezi\Foundation\Models\Partner>
 */
class EditPartner extends EditRecord
{
    protected static string $resource = PartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // CustomerFinancialWidget::class,
            VendorFinancialWidget::class,
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load custom field values
        $data['custom_fields'] = $this->record->getCustomFieldValues();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Extract custom fields data
        $customFieldsData = $data['custom_fields'] ?? [];
        unset($data['custom_fields']);

        // Save custom fields
        if (! empty($customFieldsData)) {
            $this->record->setCustomFieldValues($customFieldsData);
        }

        return $data;
    }
}
