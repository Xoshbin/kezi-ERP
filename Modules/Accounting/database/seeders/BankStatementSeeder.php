<?php

namespace Modules\Accounting\Database\Seeders;

use Brick\Money\Money;
use Illuminate\Database\Seeder;
use Modules\Accounting\Models\BankStatement;
use App\Models\Company;
use Modules\Accounting\Models\Journal;
use Modules\Foundation\Models\Partner;
use Modules\Payment\Models\Payment;

class BankStatementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::where('name', 'Jmeryar Solutions')->firstOrFail();
        $currencyCode = $company->currency->code;

        // 1. Get required partners
        $hawrePartner = Partner::where('name', 'Hawre Trading Group')->firstOrFail();
        $paykarPartner = Partner::where('name', 'Paykar Tech Supplies')->firstOrFail();

        // 2. Calculate starting balance
        $initialCapital = Money::of(15000000, $currencyCode);

        $hawrePaymentRecord = Payment::where('paid_to_from_partner_id', $hawrePartner->id)->first();
        if (! $hawrePaymentRecord) {
            $this->command->error('Payment for Hawre Trading Group not found. Please run the PaymentSeeder first.');

            return;
        }
        $hawrePayment = $hawrePaymentRecord->amount;

        $paykarPaymentRecord = Payment::where('paid_to_from_partner_id', $paykarPartner->id)->first();
        if (! $paykarPaymentRecord) {
            $this->command->error('Payment for Paykar Tech Supplies not found. Please run the PaymentSeeder first.');

            return;
        }
        $paykarPayment = $paykarPaymentRecord->amount;

        $startingBalance = $initialCapital->plus($hawrePayment)->minus($paykarPayment);

        // 3. Calculate ending balance
        $bankFee = Money::of(500, $currencyCode);
        $endingBalance = $startingBalance->minus($bankFee);

        // 4. Get the bank journal
        $bankJournal = Journal::where('name->en', 'Bank (IQD)')->where('company_id', $company->id)->firstOrFail();

        // 5. Create the Bank Statement
        $bankStatement = BankStatement::create([
            'company_id' => $company->id,
            'journal_id' => $bankJournal->id,
            'currency_id' => $company->currency_id,
            'reference' => 'BS-2025-08-001',
            'date' => now(),
            'starting_balance' => $startingBalance,
            'ending_balance' => $endingBalance,
        ]);

        // 6. Create Bank Statement Lines
        $bankStatement->bankStatementLines()->createMany([
            [
                'company_id' => $company->id,
                'date' => now(),
                'description' => 'Hawre Trading Group Payment for Invoice INV-001',
                'amount' => $hawrePayment,
                'partner_id' => $hawrePartner->id,
            ],
            [
                'company_id' => $company->id,
                'date' => now(),
                'description' => 'Payment to Paykar Tech Supplies for Laptop Bill BILL-001',
                'amount' => $paykarPayment->negated(),
                'partner_id' => $paykarPartner->id,
            ],
            [
                'company_id' => $company->id,
                'date' => now(),
                'description' => 'Monthly Bank Service Fee',
                'amount' => $bankFee->negated(),
                'partner_id' => null,
            ],
        ]);
    }
}
