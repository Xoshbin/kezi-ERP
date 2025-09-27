<?php

namespace Database\Seeders;

use App\Enums\PaymentTerms\PaymentTermType;
use App\Models\Company;
use App\Models\PaymentTerm;
use App\Models\PaymentTermLine;
use Illuminate\Database\Seeder;

class PaymentTermsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all companies to seed payment terms for each
        $companies = Company::all();

        foreach ($companies as $company) {
            $this->seedPaymentTermsForCompany($company);
        }
    }

    /**
     * Seed common payment terms for a specific company.
     */
    private function seedPaymentTermsForCompany(Company $company): void
    {
        $paymentTerms = [
            // Immediate Payment
            [
                'name' => 'Immediate',
                'description' => 'Payment due immediately upon receipt',
                'lines' => [
                    [
                        'sequence' => 1,
                        'type' => PaymentTermType::Immediate,
                        'days' => 0,
                        'percentage' => 100.0,
                    ],
                ],
            ],

            // Net Terms
            [
                'name' => 'Net 15',
                'description' => 'Payment due within 15 days',
                'lines' => [
                    [
                        'sequence' => 1,
                        'type' => PaymentTermType::Net,
                        'days' => 15,
                        'percentage' => 100.0,
                    ],
                ],
            ],
            [
                'name' => 'Net 30',
                'description' => 'Payment due within 30 days',
                'lines' => [
                    [
                        'sequence' => 1,
                        'type' => PaymentTermType::Net,
                        'days' => 30,
                        'percentage' => 100.0,
                    ],
                ],
            ],
            [
                'name' => 'Net 45',
                'description' => 'Payment due within 45 days',
                'lines' => [
                    [
                        'sequence' => 1,
                        'type' => PaymentTermType::Net,
                        'days' => 45,
                        'percentage' => 100.0,
                    ],
                ],
            ],
            [
                'name' => 'Net 60',
                'description' => 'Payment due within 60 days',
                'lines' => [
                    [
                        'sequence' => 1,
                        'type' => PaymentTermType::Net,
                        'days' => 60,
                        'percentage' => 100.0,
                    ],
                ],
            ],

            // Early Payment Discounts
            [
                'name' => '2% 10, Net 30',
                'description' => '2% discount if paid within 10 days, otherwise net 30',
                'lines' => [
                    [
                        'sequence' => 1,
                        'type' => PaymentTermType::Net,
                        'days' => 30,
                        'percentage' => 100.0,
                        'discount_percentage' => 2.0,
                        'discount_days' => 10,
                    ],
                ],
            ],
            [
                'name' => '1% 15, Net 30',
                'description' => '1% discount if paid within 15 days, otherwise net 30',
                'lines' => [
                    [
                        'sequence' => 1,
                        'type' => PaymentTermType::Net,
                        'days' => 30,
                        'percentage' => 100.0,
                        'discount_percentage' => 1.0,
                        'discount_days' => 15,
                    ],
                ],
            ],
            [
                'name' => '3% 7, Net 21',
                'description' => '3% discount if paid within 7 days, otherwise net 21',
                'lines' => [
                    [
                        'sequence' => 1,
                        'type' => PaymentTermType::Net,
                        'days' => 21,
                        'percentage' => 100.0,
                        'discount_percentage' => 3.0,
                        'discount_days' => 7,
                    ],
                ],
            ],

            // End of Month Terms
            [
                'name' => 'EOM',
                'description' => 'Payment due at the end of the month',
                'lines' => [
                    [
                        'sequence' => 1,
                        'type' => PaymentTermType::EndOfMonth,
                        'days' => 0,
                        'percentage' => 100.0,
                    ],
                ],
            ],
            [
                'name' => 'EOM + 15',
                'description' => 'Payment due 15 days after the end of the month',
                'lines' => [
                    [
                        'sequence' => 1,
                        'type' => PaymentTermType::EndOfMonth,
                        'days' => 15,
                        'percentage' => 100.0,
                    ],
                ],
            ],
            [
                'name' => 'EOM + 30',
                'description' => 'Payment due 30 days after the end of the month',
                'lines' => [
                    [
                        'sequence' => 1,
                        'type' => PaymentTermType::EndOfMonth,
                        'days' => 30,
                        'percentage' => 100.0,
                    ],
                ],
            ],

            // Day of Month Terms
            [
                'name' => '15th of Next Month',
                'description' => 'Payment due on the 15th of the following month',
                'lines' => [
                    [
                        'sequence' => 1,
                        'type' => PaymentTermType::DayOfMonth,
                        'days' => 0,
                        'day_of_month' => 15,
                        'percentage' => 100.0,
                    ],
                ],
            ],
            [
                'name' => '1st of Next Month',
                'description' => 'Payment due on the 1st of the following month',
                'lines' => [
                    [
                        'sequence' => 1,
                        'type' => PaymentTermType::DayOfMonth,
                        'days' => 0,
                        'day_of_month' => 1,
                        'percentage' => 100.0,
                    ],
                ],
            ],

            // Installment Terms
            [
                'name' => '50% Now, 50% in 30 Days',
                'description' => 'Split payment: 50% immediately, 50% in 30 days',
                'lines' => [
                    [
                        'sequence' => 1,
                        'type' => PaymentTermType::Immediate,
                        'days' => 0,
                        'percentage' => 50.0,
                    ],
                    [
                        'sequence' => 2,
                        'type' => PaymentTermType::Net,
                        'days' => 30,
                        'percentage' => 50.0,
                    ],
                ],
            ],
            [
                'name' => '30-60-90 Days',
                'description' => 'Three equal payments: 30, 60, and 90 days',
                'lines' => [
                    [
                        'sequence' => 1,
                        'type' => PaymentTermType::Net,
                        'days' => 30,
                        'percentage' => 33.33,
                    ],
                    [
                        'sequence' => 2,
                        'type' => PaymentTermType::Net,
                        'days' => 60,
                        'percentage' => 33.33,
                    ],
                    [
                        'sequence' => 3,
                        'type' => PaymentTermType::Net,
                        'days' => 90,
                        'percentage' => 33.34, // Slightly higher to handle rounding
                    ],
                ],
            ],
            [
                'name' => 'Quarterly Payments',
                'description' => 'Four equal payments every 90 days',
                'lines' => [
                    [
                        'sequence' => 1,
                        'type' => PaymentTermType::Net,
                        'days' => 90,
                        'percentage' => 25.0,
                    ],
                    [
                        'sequence' => 2,
                        'type' => PaymentTermType::Net,
                        'days' => 180,
                        'percentage' => 25.0,
                    ],
                    [
                        'sequence' => 3,
                        'type' => PaymentTermType::Net,
                        'days' => 270,
                        'percentage' => 25.0,
                    ],
                    [
                        'sequence' => 4,
                        'type' => PaymentTermType::Net,
                        'days' => 360,
                        'percentage' => 25.0,
                    ],
                ],
            ],
        ];

        foreach ($paymentTerms as $termData) {
            // Check if payment term already exists for this company
            // Since name is translatable, we need to check the JSON column
            $existingTerm = PaymentTerm::where('company_id', $company->id)
                ->whereJsonContains('name->en', $termData['name'])
                ->first();

            if ($existingTerm) {
                continue; // Skip if already exists
            }

            // Create the payment term
            $paymentTerm = PaymentTerm::create([
                'company_id' => $company->id,
                'name' => ['en' => $termData['name']],
                'description' => ['en' => $termData['description']],
                'is_active' => true,
            ]);

            // Create the payment term lines
            foreach ($termData['lines'] as $lineData) {
                PaymentTermLine::create([
                    'payment_term_id' => $paymentTerm->id,
                    'sequence' => $lineData['sequence'],
                    'type' => $lineData['type'],
                    'days' => $lineData['days'],
                    'day_of_month' => $lineData['day_of_month'] ?? null,
                    'percentage' => $lineData['percentage'],
                    'discount_percentage' => $lineData['discount_percentage'] ?? null,
                    'discount_days' => $lineData['discount_days'] ?? null,
                ]);
            }
        }
    }
}
