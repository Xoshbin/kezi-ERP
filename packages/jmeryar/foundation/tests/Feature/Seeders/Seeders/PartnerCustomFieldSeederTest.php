<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Foundation\Database\Seeders\PartnerCustomFieldSeeder;
use Jmeryar\Foundation\Models\Partner;
use Xoshbin\CustomFields\Enums\CustomFieldType;
use Xoshbin\CustomFields\Models\CustomFieldDefinition;

uses(RefreshDatabase::class);

it('creates partner custom field definition with company field', function () {
    // Run the seeder
    $this->seed(PartnerCustomFieldSeeder::class);

    // Verify the custom field definition was created
    $definition = CustomFieldDefinition::where('model_type', Partner::class)->first();

    expect($definition)->not->toBeNull();
    expect($definition->is_active)->toBeTrue();

    // Check the name and description (stored as arrays)
    expect($definition->name)->toBe('Partner Classification');
    expect($definition->description)->toContain('Additional classification fields');

    // Check the field definitions
    $fieldDefinitions = $definition->getFieldDefinitionsCollection();
    expect($fieldDefinitions)->toHaveCount(1);

    $companyField = $fieldDefinitions->firstWhere('key', 'company');
    expect($companyField)->not->toBeNull();
    expect($companyField['type'])->toBe(CustomFieldType::Boolean->value);
    expect($companyField['required'])->toBeFalse();
    expect($companyField['show_in_table'])->toBeTrue();
    expect($companyField['order'])->toBe(1);

    // Check label (stored as simple string in seeder)
    expect($companyField['label'])->toBe('Company');

    // Check help text (stored as simple string in seeder)
    expect($companyField['help_text'])->toContain('company/organization');
});

it('is idempotent and can be run multiple times', function () {
    // Run the seeder twice
    $this->seed(PartnerCustomFieldSeeder::class);
    $this->seed(PartnerCustomFieldSeeder::class);

    // Should still have only one definition
    $definitions = CustomFieldDefinition::where('model_type', Partner::class)->get();
    expect($definitions)->toHaveCount(1);

    $definition = $definitions->first();
    expect($definition->getFieldDefinitionsCollection())->toHaveCount(1);
});

it('updates existing definition if it already exists', function () {
    // Create an initial definition
    CustomFieldDefinition::create([
        'model_type' => Partner::class,
        'name' => ['en' => 'Old Name'],
        'description' => ['en' => 'Old Description'],
        'field_definitions' => [
            [
                'key' => 'old_field',
                'label' => ['en' => 'Old Field'],
                'type' => CustomFieldType::Text->value,
                'required' => false,
                'show_in_table' => false,
                'order' => 1,
            ],
        ],
        'is_active' => false,
    ]);

    // Run the seeder
    $this->seed(PartnerCustomFieldSeeder::class);

    // Should still have only one definition, but updated
    $definitions = CustomFieldDefinition::where('model_type', Partner::class)->get();
    expect($definitions)->toHaveCount(1);

    $definition = $definitions->first();
    expect($definition->name)->toBe('Partner Classification');
    expect($definition->is_active)->toBeTrue();

    // Should have the new field definition
    $fieldDefinitions = $definition->getFieldDefinitionsCollection();
    expect($fieldDefinitions)->toHaveCount(1);

    $companyField = $fieldDefinitions->firstWhere('key', 'company');
    expect($companyField)->not->toBeNull();
    expect($companyField['type'])->toBe(CustomFieldType::Boolean->value);
});
