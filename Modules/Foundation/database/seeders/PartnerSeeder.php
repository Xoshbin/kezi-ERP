<?php

namespace Database\Seeders;

use App\Enums\Accounting\AccountType;
use App\Enums\Partners\PartnerType;
use App\Models\Account;
use App\Models\Company;
use App\Models\Partner;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Seeder;

class PartnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            $company = Company::where('name', 'Jmeryar Solutions')->firstOrFail();
        } catch (ModelNotFoundException) {
            throw new Exception("Company 'Jmeryar Solutions' not found. Please run the CompanySeeder first.");
        }

        // Get default accounts for partner assignment
        $defaultReceivableAccount = Account::where('company_id', $company->id)
            ->where('code', '120101')
            ->first();

        $defaultPayableAccount = Account::where('company_id', $company->id)
            ->where('code', '210101')
            ->first();

        if (! $defaultReceivableAccount || ! $defaultPayableAccount) {
            throw new Exception('Default receivable/payable accounts not found. Please run the AccountSeeder first.');
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
            [
                'name' => 'Hiwa Computer Center',
                'type' => PartnerType::Vendor,
                'country' => 'IQ',
            ],
            [
                'name' => 'Zryan Tech Store',
                'type' => PartnerType::Customer,
                'country' => 'IQ',
            ],
        ];

        foreach ($partners as $partnerData) {
            // Create individual accounts for each partner
            $receivableAccount = Account::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => ['en' => "Accounts Receivable - {$partnerData['name']}", 'ckb' => "هەژماری وەرگرتن - {$partnerData['name']}", 'ar' => "حسابات مدينة - {$partnerData['name']}"],
                ],
                [
                    'code' => $this->getNextAvailableCode($company->id, 1200),
                    'type' => AccountType::Receivable,
                ]
            );

            $payableAccount = Account::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => ['en' => "Accounts Payable - {$partnerData['name']}", 'ckb' => "هەژماری پارەدان - {$partnerData['name']}", 'ar' => "حسابات دائنة - {$partnerData['name']}"],
                ],
                [
                    'code' => $this->getNextAvailableCode($company->id, 2100),
                    'type' => AccountType::Payable,
                ]
            );

            Partner::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => $partnerData['name'],
                ],
                [
                    'type' => $partnerData['type'],
                    'country' => $partnerData['country'],
                    'receivable_account_id' => $receivableAccount->id,
                    'payable_account_id' => $payableAccount->id,
                ]
            );
        }
    }

    private function getNextAvailableCode(int $companyId, int $baseCode): string
    {
        // Find the highest existing code within the desired range (e.g., 1200, 1201, 1202...).
        $latestCode = Account::where('company_id', $companyId)
            ->where('code', '>=', $baseCode)
            ->where('code', '<', $baseCode + 100) // Assuming max 100 partners of one type for this range
            ->max('code');

        // If no code exists in the range, start with the base code. Otherwise, increment the highest found code.
        return $latestCode ? (string) ($latestCode + 1) : (string) $baseCode;
    }
}
