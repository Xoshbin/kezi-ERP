<?php

namespace App\Filament\Components;

use App\Enums\CustomFields\CustomFieldType;
use App\Models\CustomFieldDefinition;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Schemas\Components\Fieldset;
use Illuminate\Support\Collection;

/**
 * CustomFieldsComponent
 *
 * Generates dynamic form fields based on custom field definitions.
 * Handles both create and edit operations with proper value mutations.
 */
class CustomFieldsComponent
{
    /**
     * Generate custom fields for a specific model type.
     *
     * @param string $modelClass The model class (e.g., 'App\Models\Partner')
     * @return Fieldset|null
     */
    public static function make(string $modelClass): ?Fieldset
    {
        $definition = CustomFieldDefinition::where('model_type', $modelClass)
            ->where('is_active', true)
            ->first();

        if (!$definition || empty($definition->field_definitions)) {
            return null;
        }

        $fields = static::generateFields($definition->getFieldDefinitionsCollection());

        if (empty($fields)) {
            return null;
        }

        return Fieldset::make(__('custom_fields.section_title'))
            ->schema($fields)
            ->columns(2);
    }

    /**
     * Generate form fields from field definitions.
     *
     * @param Collection<int, array> $fieldDefinitions
     * @return array
     */
    protected static function generateFields(Collection $fieldDefinitions): array
    {
        $fields = [];

        foreach ($fieldDefinitions->sortBy('order') as $fieldDefinition) {
            $field = static::generateField($fieldDefinition);

            if ($field) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * Generate a single form field from definition.
     */
    protected static function generateField(array $definition): mixed
    {
        $fieldKey = $definition['key'];
        $fieldType = CustomFieldType::tryFrom($definition['type']);
        $label = static::getTranslatedLabel($definition['label']);
        $required = $definition['required'] ?? false;
        $validationRules = $definition['validation_rules'] ?? [];

        if (!$fieldType) {
            return null;
        }

        $field = match ($fieldType) {
            CustomFieldType::Text => TextInput::make("custom_fields.{$fieldKey}")
                ->label($label)
                ->maxLength(255),

            CustomFieldType::Textarea => Textarea::make("custom_fields.{$fieldKey}")
                ->label($label)
                ->rows(3)
                ->maxLength(65535),

            CustomFieldType::Number => TextInput::make("custom_fields.{$fieldKey}")
                ->label($label)
                ->numeric(),

            CustomFieldType::Boolean => Checkbox::make("custom_fields.{$fieldKey}")
                ->label($label),

            CustomFieldType::Date => DatePicker::make("custom_fields.{$fieldKey}")
                ->label($label),

            CustomFieldType::Select => Select::make("custom_fields.{$fieldKey}")
                ->label($label)
                ->options(static::getSelectOptions($definition['options'] ?? []))
                ->searchable(),
        };

        // Apply common configurations
        if ($required) {
            $field = $field->required();
        }

        // Apply custom validation rules
        if (!empty($validationRules)) {
            $field = $field->rules($validationRules);
        }

        // Add help text if available
        if (!empty($definition['help_text'])) {
            $helpText = static::getTranslatedLabel($definition['help_text']);
            $field = $field->helperText($helpText);
        }

        return $field;
    }

    /**
     * Get translated label from definition.
     */
    protected static function getTranslatedLabel(array|string $label): string
    {
        if (is_string($label)) {
            return $label;
        }

        $locale = app()->getLocale();

        if (isset($label[$locale])) {
            return $label[$locale];
        }

        $fallbackLocale = config('app.fallback_locale', 'en');

        return $label[$fallbackLocale] ?? array_values($label)[0] ?? '';
    }

    /**
     * Get select options with translation support.
     */
    protected static function getSelectOptions(array $options): array
    {
        $result = [];

        foreach ($options as $option) {
            $value = $option['value'] ?? '';
            $label = static::getTranslatedLabel($option['label'] ?? $value);
            $result[$value] = $label;
        }

        return $result;
    }

    /**
     * Mutate form data before filling (for edit operations).
     */
    public static function mutateFormDataBeforeFill(array $data, string $modelClass): array
    {
        if (!isset($data['id'])) {
            return $data;
        }

        $model = $modelClass::find($data['id']);

        if (!$model || !method_exists($model, 'getCustomFieldValues')) {
            return $data;
        }

        $customFieldValues = $model->getCustomFieldValues();

        if (!empty($customFieldValues)) {
            $data['custom_fields'] = $customFieldValues;
        }

        return $data;
    }

    /**
     * Mutate form data before save (for create and edit operations).
     */
    public static function mutateFormDataBeforeSave(array $data, string $modelClass): array
    {
        // Extract custom fields from the main data array
        $customFields = $data['custom_fields'] ?? [];
        unset($data['custom_fields']);

        // Store custom fields separately for later processing
        $data['_custom_fields'] = $customFields;

        return $data;
    }

    /**
     * Handle custom fields after model save.
     */
    public static function handleAfterSave($record, array $data): void
    {
        if (!method_exists($record, 'setCustomFieldValues')) {
            return;
        }

        $customFields = $data['_custom_fields'] ?? [];

        if (!empty($customFields)) {
            $record->setCustomFieldValues($customFields);
        }
    }

    /**
     * Get validation rules for custom fields.
     */
    public static function getValidationRules(string $modelClass, ?int $companyId = null): array
    {
        $companyId = $companyId ?? filament()->getTenant()?->id;

        if (!$companyId) {
            return [];
        }

        $definition = CustomFieldDefinition::where('company_id', $companyId)
            ->where('model_type', $modelClass)
            ->where('is_active', true)
            ->first();

        if (!$definition) {
            return [];
        }

        $rules = [];

        foreach ($definition->getFieldDefinitionsCollection() as $fieldDefinition) {
            $fieldKey = $fieldDefinition['key'];
            $fieldType = CustomFieldType::tryFrom($fieldDefinition['type']);
            $required = $fieldDefinition['required'] ?? false;
            $customRules = $fieldDefinition['validation_rules'] ?? [];

            $fieldRules = [];

            if ($required) {
                $fieldRules[] = 'required';
            }

            if ($fieldType) {
                $fieldRules = array_merge($fieldRules, $fieldType->getValidationRules());
            }

            if (!empty($customRules)) {
                $fieldRules = array_merge($fieldRules, $customRules);
            }

            if (!empty($fieldRules)) {
                $rules["custom_fields.{$fieldKey}"] = $fieldRules;
            }
        }

        return $rules;
    }

    /**
     * Check if a model has custom fields defined.
     */
    public static function hasCustomFields(string $modelClass, ?int $companyId = null): bool
    {
        $companyId = $companyId ?? filament()->getTenant()?->id;

        if (!$companyId) {
            return false;
        }

        return CustomFieldDefinition::where('company_id', $companyId)
            ->where('model_type', $modelClass)
            ->where('is_active', true)
            ->exists();
    }
}
