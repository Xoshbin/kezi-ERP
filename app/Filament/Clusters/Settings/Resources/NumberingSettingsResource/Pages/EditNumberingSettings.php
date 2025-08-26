<?php

namespace App\Filament\Clusters\Settings\Resources\NumberingSettingsResource\Pages;

use App\Filament\Clusters\Settings\Resources\NumberingSettingsResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditNumberingSettings extends EditRecord
{
    protected static string $resource = NumberingSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No delete action since we can't delete companies from this resource
        ];
    }

    protected function beforeSave(): void
    {
        // Validate that numbering settings can be changed
        if (!$this->record->canChangeNumberingSettings()) {
            $errors = $this->record->getNumberingChangeValidationErrors();
            
            Notification::make()
                ->title(__('numbering.settings.cannot_change_title'))
                ->body(__('numbering.settings.cannot_change_message') . ' (' . implode(', ', $errors) . ')')
                ->danger()
                ->send();
                
            $this->halt();
        }
    }

    protected function afterSave(): void
    {
        Notification::make()
            ->title(__('numbering.settings.title'))
            ->body(__('filament-panels::resources/pages/edit-record.notifications.saved.title'))
            ->success()
            ->send();
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Ensure numbering_settings has default values if not set
        if (!isset($data['numbering_settings'])) {
            $data['numbering_settings'] = $this->record->getDefaultNumberingSettings();
        }

        return $data;
    }
}
