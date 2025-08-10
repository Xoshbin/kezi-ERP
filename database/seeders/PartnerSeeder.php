<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Partner;
use App\Enums\Partners\PartnerType;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PartnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            $company = Company::where('name', 'Jmeryar Solutions')->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new \Exception("Company 'Jmeryar Solutions' not found. Please run the CompanySeeder first.");
        }

        $partners = [
            [
                'name' => 'Paykar Tech Supplies',
                'type' => PartnerType::Vendor,
                'country' => 'IQ',
            ],
            [
                'name' => 'Hawre Trading Group',
                'type' => PartnerType::Customer,
                'country' => 'IQ',
            ],
        ];

        foreach ($partners as $partnerData) {
            Partner::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => $partnerData['name'],
                ],
                [
                    'type' => $partnerData['type'],
                    'country' => $partnerData['country'],
                ]
            );
        }
    }
}
