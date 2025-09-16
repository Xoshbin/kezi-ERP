<?php

namespace App\Filament\Clusters\Settings\Resources\CustomFieldDefinitions\Pages;

use App\Filament\Clusters\Settings\Resources\CustomFieldDefinitions\CustomFieldDefinitionResource;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

class CreateCustomFieldDefinition extends CreateRecord
{
    use Translatable;

    protected static string $resource = CustomFieldDefinitionResource::class;

    public function getTitle(): string
    {
        return __('Create Custom Field Definition');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure company_id is set
        $data['company_id'] = filament()->getTenant()->id;

        // Validate and clean field definitions
        if (!empty($data['field_definitions'])) {
            $data['field_definitions'] = $this->validateAndCleanFieldDefinitions($data['field_definitions']);
        }

        return $data;
    }

    protected function validateAndCleanFieldDefinitions(array $fieldDefinitions): array
    {
        $cleanedDefinitions = [];
        $keyedDefinitions = [];

        // First pass: collect all definitions by key (later ones override earlier ones)
        foreach ($fieldDefinitions as $definition) {
            // Skip empty definitions
            if (empty($definition['key']) || empty($definition['label']) || empty($definition['type'])) {
                continue;
            }

            $key = strtolower(trim($definition['key']));
            $keyedDefinitions[$key] = $definition; // Later definitions override earlier ones
        }

        // Second pass: process the final definitions
        foreach ($keyedDefinitions as $key => $definition) {

            // Clean and validate the definition
            $cleanedDefinition = [
                'key' => $key,
                'label' => $definition['label'],
                'type' => $definition['type'],
                'required' => (bool) ($definition['required'] ?? false),
                'show_in_table' => (bool) ($definition['show_in_table'] ?? false),
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

        return $cleanedDefinitions;
    }
}
