<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Journal;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class JournalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::where('name', 'Jmeryar Solutions')->first();

        if (!$company) {
            throw new \Exception('Company "Jmeryar Solutions" not found. Please run the CompanySeeder first.');
        }

        $journals = [
            [
                'name' => 'Sales',
                'type' => 'Sale',
                'short_code' => 'INV',
            ],
            [
                'name' => 'Purchases',
                'type' => 'Purchase',
                'short_code' => 'BILL',
            ],
            [
                'name' => 'Bank (USD)',
                'type' => 'Bank and Cash',
                'short_code' => 'BNK1',
            ],
            [
                'name' => 'Bank (IQD)',
                'type' => 'Bank and Cash',
                'short_code' => 'BNK2',
            ],
            [
                'name' => 'Miscellaneous',
                'type' => 'Miscellaneous',
                'short_code' => 'MISC',
            ],
        ];

        foreach ($journals as $journal) {
            Journal::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'short_code' => $journal['short_code'],
                ],
                [
                    'name' => $journal['name'],
                    'type' => $journal['type'],
                ]
            );
        }
    }
}
