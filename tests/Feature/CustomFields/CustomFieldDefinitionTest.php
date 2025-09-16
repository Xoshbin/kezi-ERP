<?php

namespace Tests\Feature\CustomFields;

use App\Enums\CustomFields\CustomFieldType;
use App\Models\Company;
use App\Models\CustomFieldDefinition;
use App\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomFieldDefinitionTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
    }

    public function test_can_create_custom_field_definition(): void
    {
        $definition = CustomFieldDefinition::create([
            'model_type' => Partner::class,
            'name' => ['en' => 'Partner Custom Fields', 'ar' => 'حقول الشريك المخصصة'],
            'description' => ['en' => 'Custom fields for partners', 'ar' => 'حقول مخصصة للشركاء'],
            'field_definitions' => [
                [
                    'key' => 'industry',
                    'label' => ['en' => 'Industry', 'ar' => 'الصناعة'],
                    'type' => CustomFieldType::Text->value,
                    'required' => false,
                    'show_in_table' => true,
                    'order' => 1,
                ],
                [
                    'key' => 'priority',
                    'label' => ['en' => 'Priority', 'ar' => 'الأولوية'],
                    'type' => CustomFieldType::Select->value,
                    'required' => true,
                    'show_in_table' => false,
                    'order' => 2,
                    'options' => [
                        ['value' => 'high', 'label' => ['en' => 'High', 'ar' => 'عالي']],
                        ['value' => 'medium', 'label' => ['en' => 'Medium', 'ar' => 'متوسط']],
                        ['value' => 'low', 'label' => ['en' => 'Low', 'ar' => 'منخفض']],
                    ],
                ],
            ],
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('custom_field_definitions', [
            'id' => $definition->id,
            'model_type' => Partner::class,
            'is_active' => true,
        ]);

        $this->assertEquals('Partner Custom Fields', $definition->getTranslation('name', 'en'));
        $this->assertEquals('حقول الشريك المخصصة', $definition->getTranslation('name', 'ar'));
        $this->assertCount(2, $definition->field_definitions);
    }

    public function test_can_add_field_definition(): void
    {
        $definition = CustomFieldDefinition::factory()->create([
            'model_type' => Partner::class,
            'field_definitions' => [],
        ]);

        $fieldData = [
            'key' => 'website',
            'label' => ['en' => 'Website', 'ar' => 'الموقع الإلكتروني'],
            'type' => CustomFieldType::Text->value,
            'required' => false,
            'order' => 1,
        ];

        $definition->addFieldDefinition($fieldData);

        $this->assertCount(1, $definition->fresh()->field_definitions);
        $this->assertEquals('website', $definition->fresh()->field_definitions[0]['key']);
    }

    public function test_can_update_field_definition(): void
    {
        $definition = CustomFieldDefinition::factory()->create([
            'model_type' => Partner::class,
            'field_definitions' => [
                [
                    'key' => 'industry',
                    'label' => ['en' => 'Industry', 'ar' => 'الصناعة'],
                    'type' => CustomFieldType::Text->value,
                    'required' => false,
                    'order' => 1,
                ],
            ],
        ]);

        $updatedData = [
            'key' => 'industry',
            'label' => ['en' => 'Business Industry', 'ar' => 'صناعة الأعمال'],
            'type' => CustomFieldType::Text->value,
            'required' => true,
            'order' => 1,
        ];

        $definition->updateFieldDefinition('industry', $updatedData);

        $fieldDef = $definition->fresh()->field_definitions[0];
        $this->assertEquals('Business Industry', $fieldDef['label']['en']);
        $this->assertTrue($fieldDef['required']);
    }

    public function test_can_remove_field_definition(): void
    {
        $definition = CustomFieldDefinition::factory()->create([
            'model_type' => Partner::class,
            'field_definitions' => [
                [
                    'key' => 'industry',
                    'label' => ['en' => 'Industry'],
                    'type' => CustomFieldType::Text->value,
                    'required' => false,
                    'order' => 1,
                ],
                [
                    'key' => 'website',
                    'label' => ['en' => 'Website'],
                    'type' => CustomFieldType::Text->value,
                    'required' => false,
                    'order' => 2,
                ],
            ],
        ]);

        $definition->removeFieldDefinition('industry');

        $this->assertCount(1, $definition->fresh()->field_definitions);
        $this->assertEquals('website', $definition->fresh()->field_definitions[0]['key']);
    }

    public function test_validates_field_definition_structure(): void
    {
        $definition = CustomFieldDefinition::factory()->create([
            'model_type' => Partner::class,
            'field_definitions' => [],
        ]);

        // Test missing required fields
        $invalidData = [
            'label' => ['en' => 'Test Field'],
            'type' => CustomFieldType::Text->value,
        ];

        $this->expectException(\InvalidArgumentException::class);
        $definition->addFieldDefinition($invalidData);
    }

    public function test_enforces_unique_constraint(): void
    {
        CustomFieldDefinition::factory()->create([
            'model_type' => Partner::class,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        CustomFieldDefinition::factory()->create([
            'model_type' => Partner::class,
        ]);
    }

    public function test_can_get_field_definition_by_key(): void
    {
        $definition = CustomFieldDefinition::factory()->create([
            'model_type' => Partner::class,
            'field_definitions' => [
                [
                    'key' => 'industry',
                    'label' => ['en' => 'Industry'],
                    'type' => CustomFieldType::Text->value,
                    'required' => false,
                    'order' => 1,
                ],
            ],
        ]);

        $fieldDef = $definition->getFieldDefinition('industry');

        $this->assertNotNull($fieldDef);
        $this->assertEquals('industry', $fieldDef['key']);
        $this->assertEquals('Industry', $fieldDef['label']['en']);
    }

    public function test_returns_null_for_non_existent_field(): void
    {
        $definition = CustomFieldDefinition::factory()->create([
            'model_type' => Partner::class,
            'field_definitions' => [],
        ]);

        $fieldDef = $definition->getFieldDefinition('non_existent');

        $this->assertNull($fieldDef);
    }

    public function test_can_filter_fields_by_show_in_table(): void
    {
        $definition = CustomFieldDefinition::create([
            'model_type' => Partner::class,
            'name' => ['en' => 'Test Fields'],
            'field_definitions' => [
                [
                    'key' => 'visible_field',
                    'label' => ['en' => 'Visible Field'],
                    'type' => CustomFieldType::Text->value,
                    'required' => false,
                    'show_in_table' => true,
                    'order' => 1,
                ],
                [
                    'key' => 'hidden_field',
                    'label' => ['en' => 'Hidden Field'],
                    'type' => CustomFieldType::Text->value,
                    'required' => false,
                    'show_in_table' => false,
                    'order' => 2,
                ],
                [
                    'key' => 'default_field',
                    'label' => ['en' => 'Default Field'],
                    'type' => CustomFieldType::Text->value,
                    'required' => false,
                    'order' => 3,
                    // No show_in_table property (should default to false)
                ],
            ],
            'is_active' => true,
        ]);

        $fieldDefinitions = $definition->getFieldDefinitionsCollection();
        $visibleFields = $fieldDefinitions->filter(fn ($field) => $field['show_in_table'] ?? false);

        $this->assertCount(1, $visibleFields);
        $this->assertEquals('visible_field', $visibleFields->first()['key']);
    }

    public function test_show_in_table_defaults_to_false(): void
    {
        $definition = CustomFieldDefinition::create([
            'model_type' => Partner::class,
            'name' => ['en' => 'Test Fields'],
            'field_definitions' => [
                [
                    'key' => 'test_field',
                    'label' => ['en' => 'Test Field'],
                    'type' => CustomFieldType::Text->value,
                    'required' => false,
                    'order' => 1,
                    // No show_in_table property
                ],
            ],
            'is_active' => true,
        ]);

        $fieldDefinitions = $definition->getFieldDefinitionsCollection();
        $field = $fieldDefinitions->first();

        $this->assertFalse($field['show_in_table'] ?? false);
    }
}
