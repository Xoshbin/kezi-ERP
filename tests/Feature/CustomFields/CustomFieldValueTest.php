<?php

namespace Tests\Feature\CustomFields;

use App\Enums\CustomFields\CustomFieldType;
use App\Models\Company;
use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldValue;
use App\Models\Partner;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomFieldValueTest extends TestCase
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
                    'label' => ['en' => 'Priority', 'ar' => 'الأولوية'],
                    'type' => CustomFieldType::Select->value,
                    'required' => true,
                    'order' => 2,
                    'options' => [
                        ['value' => 'high', 'label' => ['en' => 'High', 'ar' => 'عالي']],
                        ['value' => 'medium', 'label' => ['en' => 'Medium', 'ar' => 'متوسط']],
                    ],
                ],
                [
                    'key' => 'established_date',
                    'label' => ['en' => 'Established Date'],
                    'type' => CustomFieldType::Date->value,
                    'required' => false,
                    'order' => 3,
                ],
                [
                    'key' => 'is_preferred',
                    'label' => ['en' => 'Preferred Partner'],
                    'type' => CustomFieldType::Boolean->value,
                    'required' => false,
                    'order' => 4,
                ],
                [
                    'key' => 'annual_revenue',
                    'label' => ['en' => 'Annual Revenue'],
                    'type' => CustomFieldType::Number->value,
                    'required' => false,
                    'order' => 5,
                ],
            ],
        ]);
    }

    public function test_can_create_custom_field_value(): void
    {
        $value = CustomFieldValue::create([
            'custom_field_definition_id' => $this->definition->id,
            'customizable_type' => Partner::class,
            'customizable_id' => $this->partner->id,
            'field_key' => 'industry',
            'field_value' => ['value' => 'Technology'],
        ]);

        $this->assertDatabaseHas('custom_field_values', [
            'id' => $value->id,
            'field_key' => 'industry',
        ]);

        $this->assertEquals('Technology', $value->getValue());
    }

    public function test_can_store_translatable_values(): void
    {
        $value = CustomFieldValue::create([
            'custom_field_definition_id' => $this->definition->id,
            'customizable_type' => Partner::class,
            'customizable_id' => $this->partner->id,
            'field_key' => 'industry',
            'field_value' => [
                'translatable' => true,
                'value' => [
                    'en' => 'Technology',
                    'ar' => 'التكنولوجيا',
                ],
            ],
        ]);

        $this->assertEquals('Technology', $value->getValue('en'));
        $this->assertEquals('التكنولوجيا', $value->getValue('ar'));
    }

    public function test_casts_values_based_on_field_type(): void
    {
        // Test text value
        $textValue = CustomFieldValue::create([
            'custom_field_definition_id' => $this->definition->id,
            'customizable_type' => Partner::class,
            'customizable_id' => $this->partner->id,
            'field_key' => 'industry',
            'field_value' => ['value' => 'Technology'],
        ]);
        $this->assertIsString($textValue->getValue());

        // Test boolean value
        $boolValue = CustomFieldValue::create([
            'custom_field_definition_id' => $this->definition->id,
            'customizable_type' => Partner::class,
            'customizable_id' => $this->partner->id,
            'field_key' => 'is_preferred',
            'field_value' => ['value' => true],
        ]);
        $this->assertIsBool($boolValue->getValue());

        // Test number value
        $numberValue = CustomFieldValue::create([
            'custom_field_definition_id' => $this->definition->id,
            'customizable_type' => Partner::class,
            'customizable_id' => $this->partner->id,
            'field_key' => 'annual_revenue',
            'field_value' => ['value' => 1000000],
        ]);
        $this->assertIsNumeric($numberValue->getValue());

        // Test date value
        $dateValue = CustomFieldValue::create([
            'custom_field_definition_id' => $this->definition->id,
            'customizable_type' => Partner::class,
            'customizable_id' => $this->partner->id,
            'field_key' => 'established_date',
            'field_value' => ['value' => '2020-01-01'],
        ]);
        $this->assertInstanceOf(Carbon::class, $dateValue->getValue());
    }

    public function test_validates_select_field_options(): void
    {
        // Valid option
        $value = CustomFieldValue::create([
            'custom_field_definition_id' => $this->definition->id,
            'customizable_type' => Partner::class,
            'customizable_id' => $this->partner->id,
            'field_key' => 'priority',
            'field_value' => ['value' => 'high'],
        ]);
        $this->assertEquals('high', $value->getValue());

        // Test validation through the trait method (which has the validation logic)
        $this->expectException(\InvalidArgumentException::class);
        $this->partner->setCustomFieldValue('priority', 'invalid_option');
    }

    public function test_enforces_unique_constraint(): void
    {
        CustomFieldValue::create([
            'custom_field_definition_id' => $this->definition->id,
            'customizable_type' => Partner::class,
            'customizable_id' => $this->partner->id,
            'field_key' => 'industry',
            'field_value' => ['value' => 'Technology'],
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        CustomFieldValue::create([
            'custom_field_definition_id' => $this->definition->id,
            'customizable_type' => Partner::class,
            'customizable_id' => $this->partner->id,
            'field_key' => 'industry',
            'field_value' => ['value' => 'Finance'],
        ]);
    }

    public function test_belongs_to_definition(): void
    {
        $value = CustomFieldValue::create([
            'custom_field_definition_id' => $this->definition->id,
            'customizable_type' => Partner::class,
            'customizable_id' => $this->partner->id,
            'field_key' => 'industry',
            'field_value' => ['value' => 'Technology'],
        ]);

        $this->assertInstanceOf(CustomFieldDefinition::class, $value->definition);
        $this->assertEquals($this->definition->id, $value->definition->id);
    }

    public function test_belongs_to_customizable_model(): void
    {
        $value = CustomFieldValue::create([
            'custom_field_definition_id' => $this->definition->id,
            'customizable_type' => Partner::class,
            'customizable_id' => $this->partner->id,
            'field_key' => 'industry',
            'field_value' => ['value' => 'Technology'],
        ]);

        $this->assertInstanceOf(Partner::class, $value->customizable);
        $this->assertEquals($this->partner->id, $value->customizable->id);
    }
}
