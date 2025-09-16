<?php

namespace App\Models;

use App\Enums\CustomFields\CustomFieldType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Class CustomFieldValue
 *
 * @property int $id
 * @property int $custom_field_definition_id
 * @property string $customizable_type
 * @property int $customizable_id
 * @property string $field_key
 * @property array $field_value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CustomFieldDefinition $customFieldDefinition
 * @property-read Model $customizable
 */
class CustomFieldValue extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'custom_field_definition_id',
        'customizable_type',
        'customizable_id',
        'field_key',
        'field_value',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'field_value' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the custom field definition.
     *
     * @return BelongsTo<CustomFieldDefinition, static>
     */
    public function customFieldDefinition(): BelongsTo
    {
        return $this->belongsTo(CustomFieldDefinition::class);
    }

    /**
     * Alias for customFieldDefinition relationship.
     *
     * @return BelongsTo<CustomFieldDefinition, static>
     */
    public function definition(): BelongsTo
    {
        return $this->customFieldDefinition();
    }

    /**
     * Get the model that owns this custom field value.
     *
     * @return MorphTo<Model, static>
     */
    public function customizable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the field definition for this value.
     */
    public function getFieldDefinition(): ?array
    {
        return $this->customFieldDefinition?->getFieldDefinition($this->field_key);
    }

    /**
     * Get the field type for this value.
     */
    public function getFieldType(): ?CustomFieldType
    {
        $definition = $this->getFieldDefinition();

        if (!$definition || !isset($definition['type'])) {
            return null;
        }

        return CustomFieldType::tryFrom($definition['type']);
    }

    /**
     * Get the raw value from the field_value JSON.
     */
    public function getRawValue(): mixed
    {
        $fieldValue = $this->field_value ?? [];

        // For simple values stored as {"value": "actual_value"}
        if (isset($fieldValue['value'])) {
            return $fieldValue['value'];
        }

        // For translatable values stored as {"en": "English", "ckb": "Kurdish"}
        if (is_array($fieldValue) && !isset($fieldValue['value'])) {
            return $fieldValue;
        }

        return null;
    }

    /**
     * Get the value cast to the appropriate type.
     */
    public function getCastValue(): mixed
    {
        $fieldType = $this->getFieldType();
        $rawValue = $this->getRawValue();

        if (!$fieldType || $rawValue === null) {
            return $rawValue;
        }

        return $fieldType->castValue($rawValue);
    }

    /**
     * Get the value (alias for getTranslatedValue for backward compatibility).
     */
    public function getValue(?string $locale = null): mixed
    {
        return $this->getTranslatedValue($locale);
    }

    /**
     * Get the translated value for translatable fields.
     */
    public function getTranslatedValue(?string $locale = null): mixed
    {
        $locale = $locale ?? app()->getLocale();
        $rawValue = $this->getRawValue();

        // If it's an array (translatable), get the specific locale
        if (is_array($rawValue) && isset($rawValue[$locale])) {
            return $rawValue[$locale];
        }

        // If it's an array but locale not found, try fallback
        if (is_array($rawValue)) {
            $fallbackLocale = config('app.fallback_locale', 'en');
            return $rawValue[$fallbackLocale] ?? array_values($rawValue)[0] ?? null;
        }

        // For non-translatable fields, return the cast value
        return $this->getCastValue();
    }

    /**
     * Set the value with proper type casting and structure.
     */
    public function setValue(mixed $value, ?string $locale = null): void
    {
        $fieldType = $this->getFieldType();

        if (!$fieldType) {
            $this->field_value = ['value' => $value];
            return;
        }

        // Handle translatable fields
        if ($fieldType->supportsTranslation() && $locale) {
            $currentValue = $this->field_value ?? [];

            // Remove 'value' key if it exists (converting from non-translatable)
            if (isset($currentValue['value'])) {
                unset($currentValue['value']);
            }

            $currentValue[$locale] = $fieldType->castValue($value);
            $this->field_value = $currentValue;
        } else {
            // For non-translatable fields, store as simple value
            $this->field_value = ['value' => $fieldType->castValue($value)];
        }
    }

    /**
     * Set translatable values for all locales.
     */
    public function setTranslatableValues(array $values): void
    {
        $fieldType = $this->getFieldType();

        if (!$fieldType || !$fieldType->supportsTranslation()) {
            return;
        }

        $castValues = [];
        foreach ($values as $locale => $value) {
            $castValues[$locale] = $fieldType->castValue($value);
        }

        $this->field_value = $castValues;
    }

    /**
     * Check if this field is required.
     */
    public function isRequired(): bool
    {
        $definition = $this->getFieldDefinition();
        return $definition['required'] ?? false;
    }

    /**
     * Check if this field has a value.
     */
    public function hasValue(): bool
    {
        $value = $this->getRawValue();

        if (is_array($value)) {
            return !empty(array_filter($value, fn($v) => !empty($v)));
        }

        return !empty($value);
    }

    /**
     * Get validation rules for this field.
     */
    public function getValidationRules(): array
    {
        $definition = $this->getFieldDefinition();
        $fieldType = $this->getFieldType();

        $rules = [];

        if ($definition['required'] ?? false) {
            $rules[] = 'required';
        }

        if ($fieldType) {
            $rules = array_merge($rules, $fieldType->getValidationRules());
        }

        // Add custom validation rules from definition
        if (!empty($definition['validation_rules'])) {
            $rules = array_merge($rules, $definition['validation_rules']);
        }

        return $rules;
    }

    /**
     * Get the display value for this field.
     */
    public function getDisplayValue(?string $locale = null): string
    {
        $value = $this->getTranslatedValue($locale);
        $fieldType = $this->getFieldType();

        if ($value === null || $value === '') {
            return '';
        }

        return match ($fieldType) {
            CustomFieldType::Boolean => $value ? __('Yes') : __('No'),
            CustomFieldType::Date => $value ? Carbon::parse($value)->format('Y-m-d') : '',
            default => (string) $value,
        };
    }
}
