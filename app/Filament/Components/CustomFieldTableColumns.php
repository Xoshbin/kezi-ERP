<?php

namespace App\Filament\Components;

use App\Enums\CustomFields\CustomFieldType;
use App\Models\CustomFieldDefinition;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Collection;

/**
 * CustomFieldTableColumns
 *
 * Generates dynamic table columns for custom fields marked as show_in_table.
 * Handles different field types with proper formatting and display logic.
 */
class CustomFieldTableColumns
{
    /**
     * Generate table columns for custom fields marked as show_in_table.
     *
     * @param string $modelClass The model class (e.g., 'App\Models\Partner')
     * @return array
     */
    public static function make(string $modelClass): array
    {
        $definition = CustomFieldDefinition::where('model_type', $modelClass)
            ->where('is_active', true)
            ->first();

        if (!$definition || empty($definition->field_definitions)) {
            return [];
        }

        $fieldDefinitions = $definition->getFieldDefinitionsCollection();
        $tableFields = $fieldDefinitions->filter(fn ($field) => $field['show_in_table'] ?? false);

        if ($tableFields->isEmpty()) {
            return [];
        }

        return static::generateColumns($tableFields);
    }

    /**
     * Generate table columns from field definitions.
     *
     * @param Collection<int, array> $fieldDefinitions
     * @return array
     */
    protected static function generateColumns(Collection $fieldDefinitions): array
    {
        $columns = [];

        foreach ($fieldDefinitions->sortBy('order') as $fieldDefinition) {
            $column = static::generateColumn($fieldDefinition);
            
            if ($column) {
                $columns[] = $column;
            }
        }

        return $columns;
    }

    /**
     * Generate a single table column from field definition.
     *
     * @param array $definition
     * @return TextColumn|IconColumn|null
     */
    protected static function generateColumn(array $definition): TextColumn|IconColumn|null
    {
        $fieldKey = $definition['key'] ?? null;
        $fieldType = CustomFieldType::tryFrom($definition['type'] ?? '');
        $label = static::getFieldLabel($definition);

        if (!$fieldKey || !$fieldType || !$label) {
            return null;
        }

        $columnName = "custom_fields.{$fieldKey}";

        return match ($fieldType) {
            CustomFieldType::Text, CustomFieldType::Textarea => TextColumn::make($columnName)
                ->label($label)
                ->searchable()
                ->sortable()
                ->limit(50)
                ->tooltip(function ($record) use ($fieldKey) {
                    $value = $record->getCustomFieldValue($fieldKey);
                    return is_string($value) && strlen($value) > 50 ? $value : null;
                }),

            CustomFieldType::Number => TextColumn::make($columnName)
                ->label($label)
                ->searchable()
                ->sortable()
                ->numeric(),

            CustomFieldType::Boolean => IconColumn::make($columnName)
                ->label($label)
                ->boolean()
                ->sortable(),

            CustomFieldType::Date => TextColumn::make($columnName)
                ->label($label)
                ->searchable()
                ->sortable()
                ->date(),

            CustomFieldType::Select => TextColumn::make($columnName)
                ->label($label)
                ->searchable()
                ->sortable()
                ->formatStateUsing(function ($state) use ($definition) {
                    if (!$state) {
                        return null;
                    }

                    $options = $definition['options'] ?? [];
                    $option = collect($options)->firstWhere('value', $state);
                    
                    return $option['label'] ?? $state;
                })
                ->badge()
                ->color('gray'),
        };
    }

    /**
     * Get the field label from definition.
     *
     * @param array $definition
     * @return string|null
     */
    protected static function getFieldLabel(array $definition): ?string
    {
        $label = $definition['label'] ?? null;

        if (!$label) {
            return null;
        }

        // Handle translatable labels
        if (is_array($label)) {
            return $label[app()->getLocale()] ?? $label['en'] ?? $label[array_key_first($label)] ?? null;
        }

        return $label;
    }

    /**
     * Get searchable custom field columns for a model.
     *
     * @param string $modelClass
     * @return array
     */
    public static function getSearchableColumns(string $modelClass): array
    {
        $definition = CustomFieldDefinition::where('model_type', $modelClass)
            ->where('is_active', true)
            ->first();

        if (!$definition || empty($definition->field_definitions)) {
            return [];
        }

        $fieldDefinitions = $definition->getFieldDefinitionsCollection();
        $searchableFields = $fieldDefinitions->filter(function ($field) {
            $showInTable = $field['show_in_table'] ?? false;
            $fieldType = CustomFieldType::tryFrom($field['type'] ?? '');
            
            // Only text-based fields are searchable
            return $showInTable && in_array($fieldType, [
                CustomFieldType::Text,
                CustomFieldType::Textarea,
                CustomFieldType::Select,
            ]);
        });

        return $searchableFields->pluck('key')
            ->map(fn ($key) => "custom_fields.{$key}")
            ->toArray();
    }

    /**
     * Get sortable custom field columns for a model.
     *
     * @param string $modelClass
     * @return array
     */
    public static function getSortableColumns(string $modelClass): array
    {
        $definition = CustomFieldDefinition::where('model_type', $modelClass)
            ->where('is_active', true)
            ->first();

        if (!$definition || empty($definition->field_definitions)) {
            return [];
        }

        $fieldDefinitions = $definition->getFieldDefinitionsCollection();
        $sortableFields = $fieldDefinitions->filter(fn ($field) => $field['show_in_table'] ?? false);

        return $sortableFields->pluck('key')
            ->map(fn ($key) => "custom_fields.{$key}")
            ->toArray();
    }
}
