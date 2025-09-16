<?php

namespace App\Enums\CustomFields;

use Carbon\Carbon;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Custom Field Types
 *
 * Defines the available field types for custom fields:
 * - Text: Single line text input
 * - Textarea: Multi-line text input
 * - Number: Numeric input with validation
 * - Boolean: Checkbox/toggle input
 * - Date: Date picker input
 * - Select: Dropdown with predefined options
 */
enum CustomFieldType: string implements HasColor, HasIcon, HasLabel
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Number = 'number';
    case Boolean = 'boolean';
    case Date = 'date';
    case Select = 'select';

    /**
     * Get the translated label for the custom field type.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Text => __('enums.custom_field_type.text'),
            self::Textarea => __('enums.custom_field_type.textarea'),
            self::Number => __('enums.custom_field_type.number'),
            self::Boolean => __('enums.custom_field_type.boolean'),
            self::Date => __('enums.custom_field_type.date'),
            self::Select => __('enums.custom_field_type.select'),
        };
    }

    /**
     * Get the color for the custom field type.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::Text => 'primary',
            self::Textarea => 'info',
            self::Number => 'success',
            self::Boolean => 'warning',
            self::Date => 'purple',
            self::Select => 'orange',
        };
    }

    /**
     * Get the icon for the custom field type.
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::Text => 'heroicon-o-pencil',
            self::Textarea => 'heroicon-o-document-text',
            self::Number => 'heroicon-o-calculator',
            self::Boolean => 'heroicon-o-check-circle',
            self::Date => 'heroicon-o-calendar-days',
            self::Select => 'heroicon-o-list-bullet',
        };
    }

    /**
     * Get validation rules for this field type.
     */
    public function getValidationRules(): array
    {
        return match ($this) {
            self::Text => ['string', 'max:255'],
            self::Textarea => ['string', 'max:65535'],
            self::Number => ['numeric'],
            self::Boolean => ['boolean'],
            self::Date => ['date'],
            self::Select => ['string'],
        };
    }

    /**
     * Check if this field type supports options (like select).
     */
    public function supportsOptions(): bool
    {
        return match ($this) {
            self::Select => true,
            default => false,
        };
    }

    /**
     * Check if this field type supports translation.
     */
    public function supportsTranslation(): bool
    {
        return match ($this) {
            self::Text, self::Textarea => true,
            default => false,
        };
    }

    /**
     * Get the default value for this field type.
     */
    public function getDefaultValue(): mixed
    {
        return match ($this) {
            self::Text, self::Textarea, self::Select => '',
            self::Number => 0,
            self::Boolean => false,
            self::Date => null,
        };
    }

    /**
     * Cast value to appropriate type for this field.
     */
    public function castValue(mixed $value): mixed
    {
        // Handle arrays (translatable values) by casting each element
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $val) {
                $result[$key] = $this->castSingleValue($val);
            }
            return $result;
        }

        return $this->castSingleValue($value);
    }

    /**
     * Cast a single value to the appropriate type.
     */
    private function castSingleValue(mixed $value): mixed
    {
        return match ($this) {
            self::Text, self::Textarea, self::Select => (string) $value,
            self::Number => is_numeric($value) ? (float) $value : 0,
            self::Boolean => (bool) $value,
            self::Date => $value ? Carbon::parse($value) : null,
        };
    }
}
