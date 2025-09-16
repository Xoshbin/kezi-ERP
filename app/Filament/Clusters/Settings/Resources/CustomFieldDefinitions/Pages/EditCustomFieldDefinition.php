<?php

namespace App\Filament\Clusters\Settings\Resources\CustomFieldDefinitions\Pages;

use App\Filament\Clusters\Settings\Resources\CustomFieldDefinitions\CustomFieldDefinitionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

class EditCustomFieldDefinition extends EditRecord
{
    use Translatable;

    protected static string $resource = CustomFieldDefinitionResource::class;

    public function getTitle(): string
    {
        return __('Edit Custom Field Definition');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label(__('Delete'))
                ->requiresConfirmation()
                ->modalHeading(__('Delete Custom Field Definition'))
                ->modalDescription(__('Are you sure you want to delete this custom field definition? This will also delete all associated custom field values.'))
                ->modalSubmitActionLabel(__('Delete')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Validate and clean field definitions
        if (!empty($data['field_definitions'])) {
            $data['field_definitions'] = $this->validateAndCleanFieldDefinitions($data['field_definitions']);
        }

        return $data;
    }

    protected function validateAndCleanFieldDefinitions(array $fieldDefinitions): array
    {
        $cleanedDefinitions = [];
        $usedKeys = [];

        foreach ($fieldDefinitions as $definition) {
            // Skip empty definitions
            if (empty($definition['key']) || empty($definition['label']) || empty($definition['type'])) {
                continue;
            }

            // Ensure unique keys
            $key = strtolower(trim($definition['key']));
            if (in_array($key, $usedKeys)) {
                continue;
            }
            $usedKeys[] = $key;

            // Clean and validate the definition
            $cleanedDefinition = [
                'key' => $key,
                'label' => $definition['label'],
                'type' => $definition['type'],
                'required' => (bool) ($definition['required'] ?? false),
                'order' => (int) ($definition['order'] ?? 1),
            ];

            // Add optional fields if present
            if (!empty($definition['help_text'])) {
                $cleanedDefinition['help_text'] = $definition['help_text'];
            }

            if (!empty($definition['validation_rules'])) {
                $cleanedDefinition['validation_rules'] = array_filter(
                    array_map('trim', explode(',', $definition['validation_rules']))
                );
            }

            // Handle options for select fields
            if ($definition['type'] === 'select' && !empty($definition['options'])) {
                $cleanedOptions = [];
                foreach ($definition['options'] as $option) {
                    if (!empty($option['value']) && !empty($option['label'])) {
                        $cleanedOptions[] = [
                            'value' => trim($option['value']),
                            'label' => $option['label'],
                        ];
                    }
                }
                $cleanedDefinition['options'] = $cleanedOptions;
            }

            $cleanedDefinitions[] = $cleanedDefinition;
        }

        // Sort by order
        usort($cleanedDefinitions, fn($a, $b) => $a['order'] <=> $b['order']);

        return $cleanedDefinitions;
    }
}
