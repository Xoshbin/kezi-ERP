<?php

namespace Kezi\Foundation\Filament\Resources\NumberingSettingsResource\Pages;

use App\Models\Company;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

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
        $record = $this->getRecord();
        if (! $record instanceof Company || ! $record->canChangeNumberingSettings()) {
            $errors = method_exists($record, 'getNumberingChangeValidationErrors') ? $record->getNumberingChangeValidationErrors() : [];

            Notification::make()
                ->title(__('foundation::numbering.settings.cannot_change_title'))
                ->body(__('foundation::numbering.settings.cannot_change_message').' ('.implode(', ', $errors).')')
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function afterSave(): void
    {
        Notification::make()
            ->title(__('foundation::numbering.settings.title'))
            ->body(__('filament-panels::resources/pages/edit-record.notifications.saved.title'))
            ->success()
            ->send();
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Ensure numbering_settings has default values if not set
        if (! isset($data['numbering_settings'])) {
            $record = $this->getRecord();
            if ($record instanceof Company) {
                $data['numbering_settings'] = $record->getDefaultNumberingSettings();
            }
        }

        return $data;
    }
}
