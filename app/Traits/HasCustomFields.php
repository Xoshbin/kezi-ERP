<?php

namespace App\Traits;

use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldValue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Trait HasCustomFields
 *
 * Provides custom fields functionality for models.
 */
trait HasCustomFields
{
    /**
     * Boot the trait.
     */
    protected static function bootHasCustomFields(): void
    {
        static::deleting(function ($model) {
            $model->customFieldValues()->delete();
        });
    }

    /**
     * Get the custom field values for this model.
     *
     * @return MorphMany<CustomFieldValue, static>
     */
    public function customFieldValues(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'customizable');
    }

    /**
     * Get the custom field definition for this model type.
     */
    public function getCustomFieldDefinition(): ?CustomFieldDefinition
    {
        return CustomFieldDefinition::where('model_type', static::class)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get all custom field values as a key-value array.
     *
     * @return array<string, mixed>
     */
    public function getCustomFieldValues(?string $locale = null): array
    {
        $values = [];

        foreach ($this->customFieldValues as $customFieldValue) {
            $values[$customFieldValue->field_key] = $customFieldValue->getTranslatedValue($locale);
        }

        return $values;
    }

    /**
     * Get a specific custom field value.
     */
    public function getCustomFieldValue(string $fieldKey, ?string $locale = null): mixed
    {
        $customFieldValue = $this->customFieldValues
            ->firstWhere('field_key', $fieldKey);

        if (!$customFieldValue) {
            return null;
        }

        // If no locale specified and the value is translatable, return the full array
        $rawValue = $customFieldValue->getRawValue();
        if ($locale === null && is_array($rawValue) && !isset($rawValue['value'])) {
            return $rawValue;
        }

        return $customFieldValue->getTranslatedValue($locale);
    }

    /**
     * Set a custom field value.
     */
    public function setCustomFieldValue(string $fieldKey, mixed $value, ?string $locale = null): void
    {
        $definition = $this->getCustomFieldDefinition();

        if (!$definition) {
            throw new \InvalidArgumentException("No custom field definition found for this model.");
        }

        $fieldDefinition = $definition->getFieldDefinition($fieldKey);
        if (!$fieldDefinition) {
            throw new \InvalidArgumentException("Custom field '{$fieldKey}' is not defined for this model.");
        }

        // Validate required fields
        if (($fieldDefinition['required'] ?? false) && empty($value)) {
            throw new \InvalidArgumentException("Custom field '{$fieldKey}' is required.");
        }

        // Validate select field options
        if (($fieldDefinition['type'] ?? '') === 'select' && !empty($value)) {
            $options = $fieldDefinition['options'] ?? [];
            $validValues = array_column($options, 'value');
            if (!in_array($value, $validValues)) {
                throw new \InvalidArgumentException("Invalid option '{$value}' for select field '{$fieldKey}'.");
            }
        }

        $customFieldValue = $this->customFieldValues()
            ->where('field_key', $fieldKey)
            ->first();

        if (!$customFieldValue) {
            $customFieldValue = new CustomFieldValue([
                'custom_field_definition_id' => $definition->id,
                'customizable_type' => static::class,
                'customizable_id' => $this->id,
                'field_key' => $fieldKey,
            ]);
        }

        // Handle translatable arrays
        if (is_array($value) && $locale === null && !isset($value['value'])) {
            // This is a translatable array like ['en' => 'English', 'ar' => 'Arabic']
            $customFieldValue->setTranslatableValues($value);
        } else {
            $customFieldValue->setValue($value, $locale);
        }

        $customFieldValue->save();

        // Refresh the relationship
        $this->load('customFieldValues');
    }

    /**
     * Set multiple custom field values.
     *
     * @param array<string, mixed> $values
     */
    public function setCustomFieldValues(array $values, ?string $locale = null): void
    {
        foreach ($values as $fieldKey => $value) {
            $this->setCustomFieldValue($fieldKey, $value, $locale);
        }
    }

    /**
     * Set translatable custom field values for all locales.
     *
     * @param array<string, array<string, mixed>> $values Format: ['field_key' => ['en' => 'value', 'ckb' => 'value']]
     */
    public function setTranslatableCustomFieldValues(array $values): void
    {
        $definition = $this->getCustomFieldDefinition();

        if (!$definition) {
            return;
        }

        foreach ($values as $fieldKey => $localeValues) {
            if (!$definition->getFieldDefinition($fieldKey)) {
                continue;
            }

            $customFieldValue = $this->customFieldValues()
                ->where('field_key', $fieldKey)
                ->first();

            if (!$customFieldValue) {
                $customFieldValue = new CustomFieldValue([
                    'custom_field_definition_id' => $definition->id,
                    'customizable_type' => static::class,
                    'customizable_id' => $this->id,
                    'field_key' => $fieldKey,
                ]);
            }

            $customFieldValue->setTranslatableValues($localeValues);
            $customFieldValue->save();
        }

        // Refresh the relationship
        $this->load('customFieldValues');
    }

    /**
     * Remove a custom field value.
     */
    public function removeCustomFieldValue(string $fieldKey): bool
    {
        $customFieldValue = $this->customFieldValues()
            ->where('field_key', $fieldKey)
            ->first();

        if ($customFieldValue) {
            $customFieldValue->delete();
            $this->load('customFieldValues');
            return true;
        }

        return false;
    }

    /**
     * Check if a custom field has a value.
     */
    public function hasCustomFieldValue(string $fieldKey): bool
    {
        $customFieldValue = $this->customFieldValues
            ->firstWhere('field_key', $fieldKey);

        return $customFieldValue?->hasValue() ?? false;
    }

    /**
     * Get all defined custom fields for this model.
     *
     * @return Collection<int, array>
     */
    public function getCustomFieldDefinitions(): Collection
    {
        $definition = $this->getCustomFieldDefinition();

        return $definition ? $definition->getFieldDefinitionsCollection() : collect();
    }

    /**
     * Get custom field values with their definitions.
     *
     * @return array<string, array>
     */
    public function getCustomFieldsWithDefinitions(?string $locale = null): array
    {
        $definitions = $this->getCustomFieldDefinitions();
        $values = $this->getCustomFieldValues($locale);

        $result = [];

        foreach ($definitions as $definition) {
            $fieldKey = $definition['key'];
            $result[$fieldKey] = [
                'definition' => $definition,
                'value' => $values[$fieldKey] ?? null,
                'has_value' => $this->hasCustomFieldValue($fieldKey),
            ];
        }

        return $result;
    }

    /**
     * Validate custom field values.
     *
     * @param array<string, mixed> $values
     * @return array<string, array<string>>
     */
    public function validateCustomFieldValues(array $values): array
    {
        $definition = $this->getCustomFieldDefinition();
        $errors = [];

        if (!$definition) {
            return $errors;
        }

        foreach ($definition->getFieldDefinitionsCollection() as $fieldDefinition) {
            $fieldKey = $fieldDefinition['key'];
            $value = $values[$fieldKey] ?? null;

            // Create a temporary CustomFieldValue to get validation rules
            $tempValue = new CustomFieldValue([
                'custom_field_definition_id' => $definition->id,
                'field_key' => $fieldKey,
            ]);

            $rules = $tempValue->getValidationRules();

            if (!empty($rules)) {
                $validator = validator([$fieldKey => $value], [$fieldKey => $rules]);

                if ($validator->fails()) {
                    $errors[$fieldKey] = $validator->errors()->get($fieldKey);
                }
            }
        }

        return $errors;
    }

    /**
     * Scope to eager load custom field values.
     */
    public function scopeWithCustomFields($query)
    {
        return $query->with([
            'customFieldValues.customFieldDefinition'
        ]);
    }

    /**
     * Get the searchable custom field values for global search.
     *
     * @return array<string, string>
     */
    public function getSearchableCustomFieldValues(): array
    {
        $searchable = [];

        foreach ($this->customFieldValues as $customFieldValue) {
            $value = $customFieldValue->getDisplayValue();
            if (!empty($value)) {
                $searchable[$customFieldValue->field_key] = $value;
            }
        }

        return $searchable;
    }
}
