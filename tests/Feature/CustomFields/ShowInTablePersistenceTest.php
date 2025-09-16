<?php

use App\Enums\CustomFields\CustomFieldType;
use App\Filament\Clusters\Settings\Resources\CustomFieldDefinitions\Pages\CreateCustomFieldDefinition;
use App\Filament\Clusters\Settings\Resources\CustomFieldDefinitions\Pages\EditCustomFieldDefinition;
use App\Models\CustomFieldDefinition;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);
});

it('persists show_in_table true value through filament form', function () {
    livewire(CreateCustomFieldDefinition::class)
        ->fillForm([
            'model_type' => Product::class,
            'name' => ['en' => 'Test Custom Fields'],
            'description' => ['en' => 'Test description'],
            'field_definitions' => [
                [
                    'key' => 'visible_field',
                    'label' => ['en' => 'Visible Field'],
                    'type' => CustomFieldType::Text->value,
                    'required' => false,
                    'show_in_table' => true,
                ],
            ],
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $definition = CustomFieldDefinition::where('model_type', Product::class)->first();
    expect($definition)->not->toBeNull();

    $fieldDefinitions = $definition->getFieldDefinitionsCollection();
    $visibleField = $fieldDefinitions->firstWhere('key', 'visible_field');

    expect($visibleField)->not->toBeNull();
    expect($visibleField)->toHaveKey('show_in_table');
    expect($visibleField['show_in_table'])->toBeTrue();
});

it('persists show_in_table false value through filament form', function () {
    livewire(CreateCustomFieldDefinition::class)
        ->fillForm([
            'model_type' => Product::class,
            'name' => ['en' => 'Test Custom Fields'],
            'description' => ['en' => 'Test description'],
            'field_definitions' => [
                [
                    'key' => 'hidden_field',
                    'label' => ['en' => 'Hidden Field'],
                    'type' => CustomFieldType::Text->value,
                    'required' => false,
                    'show_in_table' => false,
                ],
            ],
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $definition = CustomFieldDefinition::where('model_type', Product::class)->first();
    expect($definition)->not->toBeNull();

    $fieldDefinitions = $definition->getFieldDefinitionsCollection();
    $hiddenField = $fieldDefinitions->firstWhere('key', 'hidden_field');

    expect($hiddenField)->not->toBeNull();
    expect($hiddenField)->toHaveKey('show_in_table');
    expect($hiddenField['show_in_table'])->toBeFalse();
});

it('persists mixed show_in_table values through filament form', function () {
    livewire(CreateCustomFieldDefinition::class)
        ->fillForm([
            'model_type' => Product::class,
            'name' => ['en' => 'Test Custom Fields'],
            'description' => ['en' => 'Test description'],
            'field_definitions' => [
                [
                    'key' => 'visible_field',
                    'label' => ['en' => 'Visible Field'],
                    'type' => CustomFieldType::Text->value,
                    'required' => false,
                    'show_in_table' => true,
                ],
                [
                    'key' => 'hidden_field',
                    'label' => ['en' => 'Hidden Field'],
                    'type' => CustomFieldType::Text->value,
                    'required' => false,
                    'show_in_table' => false,
                ],
            ],
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $definition = CustomFieldDefinition::where('model_type', Product::class)->first();
    expect($definition)->not->toBeNull();

    $fieldDefinitions = $definition->getFieldDefinitionsCollection();
    $visibleField = $fieldDefinitions->firstWhere('key', 'visible_field');
    $hiddenField = $fieldDefinitions->firstWhere('key', 'hidden_field');

    expect($visibleField)->not->toBeNull();
    expect($hiddenField)->not->toBeNull();

    expect($visibleField)->toHaveKey('show_in_table');
    expect($hiddenField)->toHaveKey('show_in_table');

    expect($visibleField['show_in_table'])->toBeTrue();
    expect($hiddenField['show_in_table'])->toBeFalse();
});

it('persists show_in_table values when editing through filament form', function () {
    // Create initial definition
    $definition = CustomFieldDefinition::factory()->create([
        'model_type' => Product::class,
        'field_definitions' => [
            [
                'key' => 'test_field',
                'label' => ['en' => 'Test Field'],
                'type' => CustomFieldType::Text->value,
                'required' => false,
                'show_in_table' => false,
            ],
        ],
    ]);

    // Edit to change show_in_table to true
    livewire(EditCustomFieldDefinition::class, [
        'record' => $definition->getRouteKey(),
    ])
        ->fillForm([
            'field_definitions' => [
                [
                    'key' => 'test_field',
                    'label' => ['en' => 'Test Field'],
                    'type' => CustomFieldType::Text->value,
                    'required' => false,
                    'show_in_table' => true,
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $definition->refresh();
    $fieldDefinitions = $definition->getFieldDefinitionsCollection();
    $testField = $fieldDefinitions->firstWhere('key', 'test_field');

    expect($testField)->not->toBeNull();
    expect($testField)->toHaveKey('show_in_table');
    expect($testField['show_in_table'])->toBeTrue();
});
