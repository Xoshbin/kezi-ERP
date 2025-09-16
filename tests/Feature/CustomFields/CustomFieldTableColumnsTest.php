<?php

namespace Tests\Feature\CustomFields;

use App\Enums\CustomFields\CustomFieldType;
use App\Filament\Components\CustomFieldTableColumns;
use App\Models\Company;
use App\Models\CustomFieldDefinition;
use App\Models\Partner;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomFieldTableColumnsTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
    }

    public function test_returns_empty_array_when_no_definition_exists(): void
    {
        $columns = CustomFieldTableColumns::make(Partner::class);

        $this->assertEmpty($columns);
    }

    public function test_returns_empty_array_when_definition_is_inactive(): void
    {
        CustomFieldDefinition::factory()->create([
            'model_type' => Partner::class,
            'is_active' => false,
            'field_definitions' => [
                [
                    'key' => 'test_field',
                    'label' => ['en' => 'Test Field'],
                    'type' => CustomFieldType::Text->value,
                    'show_in_table' => true,
                    'order' => 1,
                ],
            ],
        ]);

        $columns = CustomFieldTableColumns::make(Partner::class);

        $this->assertEmpty($columns);
    }

    public function test_returns_empty_array_when_no_fields_marked_for_table(): void
    {
        CustomFieldDefinition::factory()->create([
            'model_type' => Partner::class,
            'field_definitions' => [
                [
                    'key' => 'hidden_field',
                    'label' => ['en' => 'Hidden Field'],
                    'type' => CustomFieldType::Text->value,
                    'show_in_table' => false,
                    'order' => 1,
                ],
            ],
        ]);

        $columns = CustomFieldTableColumns::make(Partner::class);

        $this->assertEmpty($columns);
    }

    public function test_generates_text_column_for_text_field(): void
    {
        CustomFieldDefinition::factory()->create([
            'model_type' => Partner::class,
            'field_definitions' => [
                [
                    'key' => 'industry',
                    'label' => ['en' => 'Industry'],
                    'type' => CustomFieldType::Text->value,
                    'show_in_table' => true,
                    'order' => 1,
                ],
            ],
        ]);

        $columns = CustomFieldTableColumns::make(Partner::class);

        $this->assertCount(1, $columns);
        $this->assertInstanceOf(TextColumn::class, $columns[0]);
        $this->assertEquals('custom_fields.industry', $columns[0]->getName());
    }

    public function test_generates_icon_column_for_boolean_field(): void
    {
        CustomFieldDefinition::factory()->create([
            'model_type' => Partner::class,
            'field_definitions' => [
                [
                    'key' => 'is_verified',
                    'label' => ['en' => 'Verified'],
                    'type' => CustomFieldType::Boolean->value,
                    'show_in_table' => true,
                    'order' => 1,
                ],
            ],
        ]);

        $columns = CustomFieldTableColumns::make(Partner::class);

        $this->assertCount(1, $columns);
        $this->assertInstanceOf(IconColumn::class, $columns[0]);
        $this->assertEquals('custom_fields.is_verified', $columns[0]->getName());
    }

    public function test_generates_columns_for_all_field_types(): void
    {
        CustomFieldDefinition::factory()->create([
            'model_type' => Partner::class,
            'field_definitions' => [
                [
                    'key' => 'text_field',
                    'label' => ['en' => 'Text Field'],
                    'type' => CustomFieldType::Text->value,
                    'show_in_table' => true,
                    'order' => 1,
                ],
                [
                    'key' => 'number_field',
                    'label' => ['en' => 'Number Field'],
                    'type' => CustomFieldType::Number->value,
                    'show_in_table' => true,
                    'order' => 2,
                ],
                [
                    'key' => 'boolean_field',
                    'label' => ['en' => 'Boolean Field'],
                    'type' => CustomFieldType::Boolean->value,
                    'show_in_table' => true,
                    'order' => 3,
                ],
                [
                    'key' => 'date_field',
                    'label' => ['en' => 'Date Field'],
                    'type' => CustomFieldType::Date->value,
                    'show_in_table' => true,
                    'order' => 4,
                ],
                [
                    'key' => 'select_field',
                    'label' => ['en' => 'Select Field'],
                    'type' => CustomFieldType::Select->value,
                    'show_in_table' => true,
                    'order' => 5,
                    'options' => [
                        ['value' => 'option1', 'label' => ['en' => 'Option 1']],
                        ['value' => 'option2', 'label' => ['en' => 'Option 2']],
                    ],
                ],
            ],
        ]);

        $columns = CustomFieldTableColumns::make(Partner::class);

        $this->assertCount(5, $columns);

        // Text field
        $this->assertInstanceOf(TextColumn::class, $columns[0]);
        $this->assertEquals('custom_fields.text_field', $columns[0]->getName());

        // Number field
        $this->assertInstanceOf(TextColumn::class, $columns[1]);
        $this->assertEquals('custom_fields.number_field', $columns[1]->getName());

        // Boolean field
        $this->assertInstanceOf(IconColumn::class, $columns[2]);
        $this->assertEquals('custom_fields.boolean_field', $columns[2]->getName());

        // Date field
        $this->assertInstanceOf(TextColumn::class, $columns[3]);
        $this->assertEquals('custom_fields.date_field', $columns[3]->getName());

        // Select field
        $this->assertInstanceOf(TextColumn::class, $columns[4]);
        $this->assertEquals('custom_fields.select_field', $columns[4]->getName());
    }

    public function test_generates_columns_for_visible_fields(): void
    {
        CustomFieldDefinition::factory()->create([
            'model_type' => Partner::class,
            'field_definitions' => [
                [
                    'key' => 'visible_field_1',
                    'label' => ['en' => 'Visible Field 1'],
                    'type' => CustomFieldType::Text->value,
                    'show_in_table' => true,
                ],
                [
                    'key' => 'hidden_field',
                    'label' => ['en' => 'Hidden Field'],
                    'type' => CustomFieldType::Text->value,
                    'show_in_table' => false,
                ],
                [
                    'key' => 'visible_field_2',
                    'label' => ['en' => 'Visible Field 2'],
                    'type' => CustomFieldType::Text->value,
                    'show_in_table' => true,
                ],
            ],
        ]);

        $columns = CustomFieldTableColumns::make(Partner::class);

        $this->assertCount(2, $columns);

        // Check that only visible fields are included
        $columnNames = array_map(fn($column) => $column->getName(), $columns);
        $this->assertContains('custom_fields.visible_field_1', $columnNames);
        $this->assertContains('custom_fields.visible_field_2', $columnNames);
        $this->assertNotContains('custom_fields.hidden_field', $columnNames);
    }

    public function test_handles_translatable_labels(): void
    {
        app()->setLocale('ar');

        CustomFieldDefinition::factory()->create([
            'model_type' => Partner::class,
            'field_definitions' => [
                [
                    'key' => 'industry',
                    'label' => ['en' => 'Industry', 'ar' => 'الصناعة'],
                    'type' => CustomFieldType::Text->value,
                    'show_in_table' => true,
                    'order' => 1,
                ],
            ],
        ]);

        $columns = CustomFieldTableColumns::make(Partner::class);

        $this->assertCount(1, $columns);
        $this->assertEquals('الصناعة', $columns[0]->getLabel());
    }

    public function test_get_searchable_columns(): void
    {
        CustomFieldDefinition::factory()->create([
            'model_type' => Partner::class,
            'field_definitions' => [
                [
                    'key' => 'searchable_text',
                    'label' => ['en' => 'Searchable Text'],
                    'type' => CustomFieldType::Text->value,
                    'show_in_table' => true,
                    'order' => 1,
                ],
                [
                    'key' => 'non_searchable_boolean',
                    'label' => ['en' => 'Boolean'],
                    'type' => CustomFieldType::Boolean->value,
                    'show_in_table' => true,
                    'order' => 2,
                ],
                [
                    'key' => 'hidden_text',
                    'label' => ['en' => 'Hidden Text'],
                    'type' => CustomFieldType::Text->value,
                    'show_in_table' => false,
                    'order' => 3,
                ],
            ],
        ]);

        $searchableColumns = CustomFieldTableColumns::getSearchableColumns(Partner::class);

        $this->assertCount(1, $searchableColumns);
        $this->assertContains('custom_fields.searchable_text', $searchableColumns);
        $this->assertNotContains('custom_fields.non_searchable_boolean', $searchableColumns);
        $this->assertNotContains('custom_fields.hidden_text', $searchableColumns);
    }

    public function test_get_sortable_columns(): void
    {
        CustomFieldDefinition::factory()->create([
            'model_type' => Partner::class,
            'field_definitions' => [
                [
                    'key' => 'sortable_field',
                    'label' => ['en' => 'Sortable Field'],
                    'type' => CustomFieldType::Text->value,
                    'show_in_table' => true,
                ],
                [
                    'key' => 'hidden_field',
                    'label' => ['en' => 'Hidden Field'],
                    'type' => CustomFieldType::Text->value,
                    'show_in_table' => false,
                ],
            ],
        ]);

        $sortableColumns = CustomFieldTableColumns::getSortableColumns(Partner::class);

        $this->assertCount(1, $sortableColumns);
        $this->assertContains('custom_fields.sortable_field', $sortableColumns);
        $this->assertNotContains('custom_fields.hidden_field', $sortableColumns);
    }
}
