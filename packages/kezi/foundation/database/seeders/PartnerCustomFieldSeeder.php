<?php

namespace Kezi\Foundation\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Kezi\Foundation\Models\Partner;
use Xoshbin\CustomFields\Enums\CustomFieldType;
use Xoshbin\CustomFields\Models\CustomFieldDefinition;

class PartnerCustomFieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Create or update the Partner custom field definition
            // Note: Custom field definitions are global (not company-specific) based on current schema
            CustomFieldDefinition::updateOrCreate(
                [
                    'model_type' => Partner::class,
                ],
                [
                    'name' => 'Partner Classification',
                    'description' => 'Additional classification fields for partners to distinguish between companies and individuals',
                    'field_definitions' => [
                        [
                            'key' => 'company',
                            'label' => 'Company',
                            'type' => CustomFieldType::Boolean->value,
                            'required' => false,
                            'show_in_table' => true,
                            'order' => 1,
                            'help_text' => 'Check this box if the partner is a company/organization rather than an individual',
                        ],
                    ],
                    'is_active' => true,
                ]
            );

            $this->command->info('Partner custom field definition created successfully.');
        });
    }
}
