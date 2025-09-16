<?php

namespace Tests\Feature\CustomFields;

use App\Enums\CustomFields\CustomFieldType;
use App\Models\Company;
use App\Models\CustomFieldDefinition;
use App\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasCustomFieldsTraitTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected CustomFieldDefinition $definition;
    protected Partner $partner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->partner = Partner::factory()->create(['company_id' => $this->company->id]);

        $this->definition = CustomFieldDefinition::factory()->create([
            'model_type' => Partner::class,
            'field_definitions' => [
                [
                    'key' => 'industry',
                    'label' => ['en' => 'Industry', 'ar' => 'الصناعة'],
                    'type' => CustomFieldType::Text->value,
                    'required' => false,
                    'order' => 1,
                ],
                [
                    'key' => 'priority',
                    'label' => ['en' => 'Priority'],
                    'type' => CustomFieldType::Select->value,
                    'required' => true,
                    'order' => 2,
                    'options' => [
                        ['value' => 'high', 'label' => ['en' => 'High']],
                        ['value' => 'medium', 'label' => ['en' => 'Medium']],
                    ],
                ],
                [
                    'key' => 'is_preferred',
                    'label' => ['en' => 'Preferred Partner'],
                    'type' => CustomFieldType::Boolean->value,
                    'required' => false,
                    'order' => 3,
                ],
            ],
        ]);
    }

    public function test_can_set_custom_field_values(): void
    {
        $values = [
            'industry' => 'Technology',
            'priority' => 'high',
            'is_preferred' => true,
        ];

        $this->partner->setCustomFieldValues($values);

        $this->assertDatabaseHas('custom_field_values', [
            'customizable_type' => Partner::class,
            'customizable_id' => $this->partner->id,
            'field_key' => 'industry',
        ]);

        $this->assertDatabaseHas('custom_field_values', [
            'customizable_type' => Partner::class,
            'customizable_id' => $this->partner->id,
            'field_key' => 'priority',
        ]);

        $this->assertDatabaseHas('custom_field_values', [
            'customizable_type' => Partner::class,
            'customizable_id' => $this->partner->id,
            'field_key' => 'is_preferred',
        ]);
    }

    public function test_can_get_custom_field_values(): void
    {
        $values = [
            'industry' => 'Technology',
            'priority' => 'high',
            'is_preferred' => true,
        ];

        $this->partner->setCustomFieldValues($values);
        $retrievedValues = $this->partner->getCustomFieldValues();

        $this->assertEquals('Technology', $retrievedValues['industry']);
        $this->assertEquals('high', $retrievedValues['priority']);
        $this->assertTrue($retrievedValues['is_preferred']);
    }

    public function test_can_get_single_custom_field_value(): void
    {
        $this->partner->setCustomFieldValues(['industry' => 'Technology']);

        $value = $this->partner->getCustomFieldValue('industry');
        $this->assertEquals('Technology', $value);

        $nonExistentValue = $this->partner->getCustomFieldValue('non_existent');
        $this->assertNull($nonExistentValue);
    }

    public function test_can_set_single_custom_field_value(): void
    {
        $this->partner->setCustomFieldValue('industry', 'Finance');

        $this->assertDatabaseHas('custom_field_values', [
            'customizable_type' => Partner::class,
            'customizable_id' => $this->partner->id,
            'field_key' => 'industry',
        ]);

        $this->assertEquals('Finance', $this->partner->getCustomFieldValue('industry'));
    }

    public function test_updates_existing_custom_field_value(): void
    {
        $this->partner->setCustomFieldValue('industry', 'Technology');
        $this->assertEquals('Technology', $this->partner->getCustomFieldValue('industry'));

        $this->partner->setCustomFieldValue('industry', 'Finance');
        $this->assertEquals('Finance', $this->partner->getCustomFieldValue('industry'));

        // Should only have one record
        $this->assertEquals(1, $this->partner->customFieldValues()->where('field_key', 'industry')->count());
    }

    public function test_validates_required_fields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Custom field 'priority' is required.");

        // Try to set empty value for required 'priority' field
        $this->partner->setCustomFieldValue('priority', '');
    }

    public function test_validates_field_existence(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Custom field 'non_existent' is not defined for this model.");

        $this->partner->setCustomFieldValue('non_existent', 'value');
    }

    public function test_validates_select_field_options(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid option 'invalid_option' for select field 'priority'.");

        $this->partner->setCustomFieldValue('priority', 'invalid_option');
    }

    public function test_can_handle_translatable_values(): void
    {
        $translatableValue = [
            'en' => 'Technology',
            'ar' => 'التكنولوجيا',
        ];

        $this->partner->setCustomFieldValue('industry', $translatableValue);
        $retrievedValue = $this->partner->getCustomFieldValue('industry');

        $this->assertEquals('Technology', $retrievedValue['en']);
        $this->assertEquals('التكنولوجيا', $retrievedValue['ar']);
    }

    public function test_has_custom_field_values_relationship(): void
    {
        $this->partner->setCustomFieldValues([
            'industry' => 'Technology',
            'priority' => 'high',
        ]);

        $this->assertCount(2, $this->partner->customFieldValues);
        $this->assertEquals('industry', $this->partner->customFieldValues->first()->field_key);
    }

    public function test_can_eager_load_custom_field_values(): void
    {
        $this->partner->setCustomFieldValues([
            'industry' => 'Technology',
            'priority' => 'high',
        ]);

        $partnerWithCustomFields = Partner::with('customFieldValues')->find($this->partner->id);

        $this->assertTrue($partnerWithCustomFields->relationLoaded('customFieldValues'));
        $this->assertCount(2, $partnerWithCustomFields->customFieldValues);
    }

    public function test_deletes_custom_field_values_when_model_deleted(): void
    {
        $this->partner->setCustomFieldValues([
            'industry' => 'Technology',
            'priority' => 'high',
        ]);

        $this->assertDatabaseCount('custom_field_values', 2);

        $this->partner->delete();

        $this->assertDatabaseCount('custom_field_values', 0);
    }

    public function test_returns_empty_array_when_no_definition_exists(): void
    {
        // Create a partner without custom field definition
        $partnerWithoutDefinition = Partner::factory()->create(['company_id' => $this->company->id]);

        // Delete the definition
        $this->definition->delete();

        $values = $partnerWithoutDefinition->getCustomFieldValues();
        $this->assertEquals([], $values);
    }
}
