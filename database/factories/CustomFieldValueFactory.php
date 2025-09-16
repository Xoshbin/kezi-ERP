<?php

namespace Database\Factories;

use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldValue;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomFieldValue>
 */
class CustomFieldValueFactory extends Factory
{
    protected $model = CustomFieldValue::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'custom_field_definition_id' => CustomFieldDefinition::factory(),
            'customizable_type' => Partner::class,
            'customizable_id' => Partner::factory(),
            'field_key' => 'sample_field',
            'field_value' => [
                'value' => $this->faker->word(),
            ],
        ];
    }

    /**
     * Create a value for a specific field key.
     */
    public function forField(string $fieldKey): static
    {
        return $this->state(fn (array $attributes) => [
            'field_key' => $fieldKey,
        ]);
    }

    /**
     * Create a text field value.
     */
    public function textValue(string $value = null): static
    {
        return $this->state(fn (array $attributes) => [
            'field_value' => [
                'value' => $value ?? $this->faker->sentence(),
            ],
        ]);
    }

    /**
     * Create a translatable text field value.
     */
    public function translatableTextValue(array $translations = null): static
    {
        $defaultTranslations = [
            'en' => $this->faker->sentence(),
            'ar' => 'نص باللغة العربية',
            'ckb' => 'دەقی کوردی',
        ];

        return $this->state(fn (array $attributes) => [
            'field_value' => [
                'translatable' => true,
                'value' => $translations ?? $defaultTranslations,
            ],
        ]);
    }

    /**
     * Create a number field value.
     */
    public function numberValue(int|float $value = null): static
    {
        return $this->state(fn (array $attributes) => [
            'field_value' => [
                'value' => $value ?? $this->faker->randomNumber(),
            ],
        ]);
    }

    /**
     * Create a boolean field value.
     */
    public function booleanValue(bool $value = null): static
    {
        return $this->state(fn (array $attributes) => [
            'field_value' => [
                'value' => $value ?? $this->faker->boolean(),
            ],
        ]);
    }

    /**
     * Create a date field value.
     */
    public function dateValue(string $value = null): static
    {
        return $this->state(fn (array $attributes) => [
            'field_value' => [
                'value' => $value ?? $this->faker->date(),
            ],
        ]);
    }

    /**
     * Create a select field value.
     */
    public function selectValue(string $value = null): static
    {
        $options = ['option1', 'option2', 'option3'];
        
        return $this->state(fn (array $attributes) => [
            'field_value' => [
                'value' => $value ?? $this->faker->randomElement($options),
            ],
        ]);
    }

    /**
     * Create a textarea field value.
     */
    public function textareaValue(string $value = null): static
    {
        return $this->state(fn (array $attributes) => [
            'field_value' => [
                'value' => $value ?? $this->faker->paragraph(),
            ],
        ]);
    }

    /**
     * Create a value for a specific customizable model.
     */
    public function forModel(string $modelType, int $modelId): static
    {
        return $this->state(fn (array $attributes) => [
            'customizable_type' => $modelType,
            'customizable_id' => $modelId,
        ]);
    }

    /**
     * Create a value for a specific definition.
     */
    public function forDefinition(CustomFieldDefinition $definition): static
    {
        return $this->state(fn (array $attributes) => [
            'custom_field_definition_id' => $definition->id,
        ]);
    }
}
