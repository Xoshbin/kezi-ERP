<?php

use App\Enums\CustomFields\CustomFieldType;
use App\Filament\Clusters\Settings\Resources\CustomFieldDefinitions\Pages\CreateCustomFieldDefinition;
use App\Filament\Components\CustomFieldTableColumns;
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

it('complete show_in_table integration test', function () {
    // Step 1: Create a custom field definition with show_in_table: true through Filament
    livewire(CreateCustomFieldDefinition::class)
        ->fillForm([
            'model_type' => Product::class,
            'name' => ['en' => 'Product Custom Fields'],
            'description' => ['en' => 'Custom fields for products'],
            'field_definitions' => [
                [
                    'key' => 'brand',
                    'label' => ['en' => 'Brand'],
                    'type' => CustomFieldType::Text->value,
                    'required' => false,
                    'show_in_table' => true,
                ],
                [
                    'key' => 'internal_notes',
                    'label' => ['en' => 'Internal Notes'],
                    'type' => CustomFieldType::Textarea->value,
                    'required' => false,
                    'show_in_table' => false,
                ],
            ],
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Step 2: Verify the definition was saved correctly
    $definition = CustomFieldDefinition::where('model_type', Product::class)->first();
    expect($definition)->not->toBeNull();

    $fieldDefinitions = $definition->getFieldDefinitionsCollection();
    $brandField = $fieldDefinitions->firstWhere('key', 'brand');
    $notesField = $fieldDefinitions->firstWhere('key', 'internal_notes');

    expect($brandField)->not->toBeNull();
    expect($notesField)->not->toBeNull();
    expect($brandField['show_in_table'])->toBeTrue();
    expect($notesField['show_in_table'])->toBeFalse();

    // Step 3: Verify CustomFieldTableColumns generates columns correctly
    $columns = CustomFieldTableColumns::make(Product::class);
    
    expect($columns)->toHaveCount(1); // Only the brand field should generate a column
    expect($columns[0]->getName())->toBe('custom_fields.brand');

    // Step 4: Verify searchable and sortable columns
    $searchableColumns = CustomFieldTableColumns::getSearchableColumns(Product::class);
    $sortableColumns = CustomFieldTableColumns::getSortableColumns(Product::class);

    expect($searchableColumns)->toContain('custom_fields.brand');
    expect($searchableColumns)->not->toContain('custom_fields.internal_notes');
    expect($sortableColumns)->toContain('custom_fields.brand');
    expect($sortableColumns)->not->toContain('custom_fields.internal_notes');
});

it('show_in_table field works correctly after editing', function () {
    // Create initial definition with show_in_table: false
    $definition = CustomFieldDefinition::factory()->create([
        'model_type' => Product::class,
        'field_definitions' => [
            [
                'key' => 'category',
                'label' => ['en' => 'Category'],
                'type' => CustomFieldType::Text->value,
                'required' => false,
                'show_in_table' => false,
            ],
        ],
    ]);

    // Verify no columns are generated initially
    $columns = CustomFieldTableColumns::make(Product::class);
    expect($columns)->toHaveCount(0);

    // Edit to change show_in_table to true
    livewire(\App\Filament\Clusters\Settings\Resources\CustomFieldDefinitions\Pages\EditCustomFieldDefinition::class, [
        'record' => $definition->getRouteKey(),
    ])
        ->fillForm([
            'field_definitions' => [
                [
                    'key' => 'category',
                    'label' => ['en' => 'Category'],
                    'type' => CustomFieldType::Text->value,
                    'required' => false,
                    'show_in_table' => true,
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    // Verify the field was updated correctly
    $definition->refresh();
    $fieldDefinitions = $definition->getFieldDefinitionsCollection();
    $categoryField = $fieldDefinitions->firstWhere('key', 'category');

    expect($categoryField)->not->toBeNull();
    expect($categoryField['show_in_table'])->toBeTrue();

    // Verify columns are now generated
    $columns = CustomFieldTableColumns::make(Product::class);
    expect($columns)->toHaveCount(1);
    expect($columns[0]->getName())->toBe('custom_fields.category');
});
