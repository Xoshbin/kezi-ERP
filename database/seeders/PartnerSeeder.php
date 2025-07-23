<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Partner;
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
                'name' => 'Walk-in Customer',
                'type' => 'Customer',
                'country' => 'IQ',
            ],
            [
                'name' => 'Default Vendor',
                'type' => 'Vendor',
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
