<?php

namespace App\Models;

use App\Enums\CustomFields\CustomFieldType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\Translatable\HasTranslations;

/**
 * Class CustomFieldDefinition
 *
 * @property int $id
 * @property int $company_id
 * @property string $model_type
 * @property array $field_definitions
 * @property array<string, string> $name
 * @property array<string, string>|null $description
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read Collection<int, CustomFieldValue> $customFieldValues
 * @property-read int|null $custom_field_values_count
 */
class CustomFieldDefinition extends Model
{
    use HasFactory, HasTranslations;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'model_type',
        'field_definitions',
        'name',
        'description',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'field_definitions' => 'array',
        'name' => 'array',
        'description' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Mutator for field_definitions to ensure show_in_table has default values and proper type casting.
     */
    public function setFieldDefinitionsAttribute($value): void
    {
        if (is_array($value)) {
            $value = array_map(function ($field) {
                // Ensure show_in_table has a default value and convert to boolean
                if (!isset($field['show_in_table'])) {
                    $field['show_in_table'] = false;
                } else {
                    // Convert string values from Filament forms to boolean
                    $field['show_in_table'] = filter_var($field['show_in_table'], FILTER_VALIDATE_BOOLEAN);
                }
                return $field;
            }, $value);
        }

        $this->attributes['field_definitions'] = json_encode($value);
    }

    /**
     * The attributes that are translatable.
     *
     * @var array<int, string>
     */
    public array $translatable = ['name', 'description'];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'field_definitions' => '[]',
    ];

    /**
     * Get the translatable fields that should be searched.
     *
     * @return array<int, string>
     */
    public function getTranslatableSearchFields(): array
    {
        return ['name', 'description'];
    }

    /**
     * Get the non-translatable fields that should be searched.
     *
     * @return array<int, string>
     */
    public function getNonTranslatableSearchFields(): array
    {
        return ['model_type'];
    }

    /**
     * Get the custom field values for this definition.
     *
     * @return HasMany<CustomFieldValue, static>
     */
    public function customFieldValues(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class);
    }

    /**
     * Get the field definitions as a collection with proper structure.
     *
     * @return Collection<int, array>
     */
    public function getFieldDefinitionsCollection(): Collection
    {
        return collect($this->field_definitions ?? []);
    }

    /**
     * Get a specific field definition by key.
     */
    public function getFieldDefinition(string $key): ?array
    {
        return $this->getFieldDefinitionsCollection()
            ->firstWhere('key', $key);
    }

    /**
     * Add a new field definition.
     */
    public function addFieldDefinition(array $fieldDefinition): void
    {
        $this->validateFieldDefinition($fieldDefinition);

        $definitions = $this->field_definitions ?? [];
        $definitions[] = $fieldDefinition;
        $this->field_definitions = $definitions;
        $this->save();
    }

    /**
     * Update a field definition by key.
     */
    public function updateFieldDefinition(string $key, array $fieldDefinition): bool
    {
        $this->validateFieldDefinition($fieldDefinition);

        $definitions = $this->field_definitions ?? [];

        foreach ($definitions as $index => $definition) {
            if ($definition['key'] === $key) {
                $definitions[$index] = $fieldDefinition;
                $this->field_definitions = $definitions;
                $this->save();
                return true;
            }
        }

        return false;
    }

    /**
     * Remove a field definition by key.
     */
    public function removeFieldDefinition(string $key): bool
    {
        $definitions = $this->field_definitions ?? [];
        $originalCount = count($definitions);

        $definitions = array_filter($definitions, fn($definition) => $definition['key'] !== $key);

        if (count($definitions) < $originalCount) {
            $this->field_definitions = array_values($definitions);
            $this->save();
            return true;
        }

        return false;
    }

    /**
     * Get all field keys from the definitions.
     *
     * @return array<int, string>
     */
    public function getFieldKeys(): array
    {
        return $this->getFieldDefinitionsCollection()
            ->pluck('key')
            ->toArray();
    }

    /**
     * Validate field definition structure.
     */
    protected function validateFieldDefinition(array $fieldDefinition): void
    {
        $required = ['key', 'label', 'type'];

        foreach ($required as $field) {
            if (!isset($fieldDefinition[$field]) || empty($fieldDefinition[$field])) {
                throw new \InvalidArgumentException("Field {$field} is required");
            }
        }

        // Validate field type
        if (!CustomFieldType::tryFrom($fieldDefinition['type'])) {
            throw new \InvalidArgumentException("Invalid field type: {$fieldDefinition['type']}");
        }
    }

    /**
     * Validate field definitions structure.
     */
    public function validateFieldDefinitions(): array
    {
        $errors = [];
        $definitions = $this->field_definitions ?? [];
        $keys = [];

        foreach ($definitions as $index => $definition) {
            $fieldErrors = [];

            // Check required fields
            if (empty($definition['key'])) {
                $fieldErrors[] = 'Key is required';
            } elseif (in_array($definition['key'], $keys)) {
                $fieldErrors[] = 'Key must be unique';
            } else {
                $keys[] = $definition['key'];
            }

            if (empty($definition['label'])) {
                $fieldErrors[] = 'Label is required';
            }

            if (empty($definition['type'])) {
                $fieldErrors[] = 'Type is required';
            } elseif (!in_array($definition['type'], array_column(CustomFieldType::cases(), 'value'))) {
                $fieldErrors[] = 'Invalid field type';
            }

            // Check if select type has options
            if ($definition['type'] === CustomFieldType::Select->value && empty($definition['options'])) {
                $fieldErrors[] = 'Select fields must have options';
            }

            if (!empty($fieldErrors)) {
                $errors["field_{$index}"] = $fieldErrors;
            }
        }

        return $errors;
    }

    /**
     * Get the model class name without namespace.
     */
    public function getModelName(): string
    {
        return class_basename($this->model_type);
    }

    /**
     * Check if this definition supports a specific model.
     */
    public function supportsModel(string $modelClass): bool
    {
        return $this->model_type === $modelClass;
    }
}
