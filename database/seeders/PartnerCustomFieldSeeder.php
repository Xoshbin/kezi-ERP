<?php

namespace Database\Seeders;

use App\Enums\CustomFields\CustomFieldType;
use App\Models\CustomFieldDefinition;
use App\Models\Partner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
                    'name' => [
                        'en' => 'Partner Classification',
                        'ar' => 'تصنيف الشريك',
                        'ckb' => 'پۆلێنکردنی هاوبەش',
                    ],
                    'description' => [
                        'en' => 'Additional classification fields for partners to distinguish between companies and individuals',
                        'ar' => 'حقول تصنيف إضافية للشركاء للتمييز بين الشركات والأفراد',
                        'ckb' => 'خانەی پۆلێنکردنی زیادە بۆ هاوبەشان بۆ جیاکردنەوەی نێوان کۆمپانیا و تاکەکان',
                    ],
                    'field_definitions' => [
                        [
                            'key' => 'company',
                            'label' => [
                                'en' => 'Company',
                                'ar' => 'شركة',
                                'ckb' => 'کۆمپانیا',
                            ],
                            'type' => CustomFieldType::Boolean->value,
                            'required' => false,
                            'show_in_table' => true,
                            'order' => 1,
                            'help_text' => [
                                'en' => 'Check this box if the partner is a company/organization rather than an individual',
                                'ar' => 'حدد هذا المربع إذا كان الشريك شركة/منظمة وليس فردًا',
                                'ckb' => 'ئەم سندوقە نیشان بکە ئەگەر هاوبەشەکە کۆمپانیا/ڕێکخراوە نەک تاکەکەس',
                            ],
                        ],
                    ],
                    'is_active' => true,
                ]
            );

            $this->command->info('Partner custom field definition created successfully.');
        });
    }
}
