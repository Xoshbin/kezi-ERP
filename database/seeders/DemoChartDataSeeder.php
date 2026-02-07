<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DemoChartDataSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = DB::table('companies')->value('id');
        $currencyId = DB::table('currencies')->where('code', 'IQD')->value('id');
        // Fallback if IQD not found
        if (!$currencyId) {
             $currencyId = DB::table('currencies')->value('id');
        }

        if (!$companyId || !$currencyId) {
            $this->command->error("No company or currency found. Please run basic seeders first.");
            return;
        }

        $user = DB::table('users')->value('id'); // Pick first user

        // Get Accounts
        $accounts = DB::table('accounts')->where('company_id', $companyId)->get()->keyBy('code');
        // Fallback codes based on ScenarioOneSeeder
        $bankAccountId = $accounts['1010']->id ?? null;
        $arAccountId = $accounts['1200']->id ?? null;
        $apAccountId = $accounts['2100']->id ?? null;
        $incomeAccountId = $accounts['4000']->id ?? null;
        $expenseAccountId = $accounts['1500']->id ?? null; // IT Equipment as generic expense for demo

        // Get Journals
        $salesJournalId = DB::table('journals')->where('type', 'sale')->value('id');
        $purchaseJournalId = DB::table('journals')->where('type', 'purchase')->value('id');
        $bankJournalId = DB::table('journals')->where('type', 'bank')->value('id');
        
        // Setup Partners
        $customerIds = DB::table('partners')->where('type', 'Customer')->pluck('id')->toArray();
        if (empty($customerIds)) {
             // Create a dummy customer if none exists
             $customerIds[] = DB::table('partners')->insertGetId([
                'company_id' => $companyId,
                'name' => 'Demo Customer', 
                'type' => 'Customer',
                'is_active' => true, 
                'created_at' => now(), 'updated_at' => now()
             ]);
        }

        $vendorIds = DB::table('partners')->where('type', 'Vendor')->pluck('id')->toArray();
        if (empty($vendorIds)) {
             $vendorIds[] = DB::table('partners')->insertGetId([
                'company_id' => $companyId,
                'name' => 'Demo Vendor',
                'type' => 'Vendor',
                'is_active' => true,
                'created_at' => now(), 'updated_at' => now()
             ]);
        }

        $startDate = Carbon::now()->subYear()->startOfMonth();
        $endDate = Carbon::now();

        $this->command->info("Generating data from {$startDate->toDateString()} to {$endDate->toDateString()}");

        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $month = $currentDate->format('F Y');
            $this->command->info("Processing $month...");
            
            // Generate Random Invoices (Income)
            $numInvoices = rand(2, 5); 
            for ($i = 0; $i < $numInvoices; $i++) {
                $this->createInvoice(
                    $companyId, $currencyId, $user, 
                    $customerIds[array_rand($customerIds)], 
                    $currentDate, 
                    $salesJournalId, $arAccountId, $incomeAccountId,
                    $bankJournalId, $bankAccountId
                );
            }

            // Generate Random Bills (Expenses)
            $numBills = rand(1, 4);
            for ($i = 0; $i < $numBills; $i++) {
                $this->createBill(
                    $companyId, $currencyId, $user, 
                    $vendorIds[array_rand($vendorIds)], 
                    $currentDate, 
                    $purchaseJournalId, $apAccountId, $expenseAccountId,
                    $bankJournalId, $bankAccountId
                );
            }

            $currentDate->addMonth();
        }
        
        $this->command->info("Done!");
    }

    private function createInvoice($companyId, $currencyId, $userId, $customerId, $monthDate, $journalId, $arAccountId, $incomeAccountId, $bankJournalId, $bankAccountId) 
    {
        $date = $monthDate->copy()->addDays(rand(1, 28));
        $amount = rand(500000, 5000000); // Random amount between 500k and 5M IQD

        // 1. Invoice
        $invoiceId = DB::table('invoices')->insertGetId([
            'company_id' => $companyId,
            'currency_id' => $currencyId,
            'customer_id' => $customerId,
            'invoice_date' => $date,
            'due_date' => $date->copy()->addDays(30),
            'status' => 'posted', // Assuming we want them posted for charts
            'invoice_number' => 'INV-DEMO-' . Str::random(5),
            'total_amount' => $amount,
            'total_tax' => 0,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        
        DB::table('invoice_lines')->insert([
            'company_id' => $companyId,
            'invoice_id' => $invoiceId,
            'description' => 'Demo Service',
            'quantity' => 1,
            'unit_price' => $amount,
            'subtotal' => $amount,
            'income_account_id' => $incomeAccountId,
            'total_line_tax' => 0,
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        // 2. Invoice Journal Entry
        $jeHash = hash('sha256', 'inv_demo_'.Str::random(10));
        $jeId = DB::table('journal_entries')->insertGetId([
            'company_id' => $companyId,
            'journal_id' => $journalId,
            'currency_id' => $currencyId,
            'entry_date' => $date,
            'entry_number' => 'INV/DEMO/' . Str::random(5),
            'reference' => 'Demo Data',
            'description' => 'Demo Invoice',
            'created_by_user_id' => $userId,
            'is_posted' => true,
            'state' => 'posted',
            'total_debit' => $amount,
            'total_credit' => $amount,
            'hash' => $jeHash,
            'source_type' => 'Kezi\Sales\Models\Invoice',
            'source_id' => $invoiceId,
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        // Debit AR, Credit Income
        DB::table('journal_entry_lines')->insert([
            [
                'company_id' => $companyId,
                'journal_entry_id' => $jeId,
                'account_id' => $arAccountId,
                'currency_id' => $currencyId,
                'original_currency_id' => $currencyId,
                'debit' => $amount,
                'credit' => 0,
                'original_currency_amount' => $amount,
                'exchange_rate_at_transaction' => 1,
                'description' => 'AR',
                'created_at' => $date,
                'updated_at' => $date,
            ],
            [
                'company_id' => $companyId,
                'journal_entry_id' => $jeId,
                'account_id' => $incomeAccountId,
                'currency_id' => $currencyId,
                'original_currency_id' => $currencyId,
                'debit' => 0,
                'credit' => $amount,
                'original_currency_amount' => $amount,
                'exchange_rate_at_transaction' => 1,
                'description' => 'Income',
                'created_at' => $date,
                'updated_at' => $date,
            ]
        ]);
        
        DB::table('invoices')->where('id', $invoiceId)->update(['journal_entry_id' => $jeId]);

        // 3. Payment (Randomly pay some invoices)
        if (rand(0, 10) > 2) { // 80% chance of payment
            $paymentDate = $date->copy()->addDays(rand(1, 15));
            if ($paymentDate > Carbon::now()) $paymentDate = Carbon::now();

            $paymentId = DB::table('payments')->insertGetId([
                'company_id' => $companyId,
                'currency_id' => $currencyId,
                'payment_type' => 'Inbound', // Receiving money
                'paid_to_from_partner_id' => $customerId,
                'amount' => $amount,
                'payment_date' => $paymentDate,
                'journal_id' => $bankJournalId,
                'status' => 'Confirmed',
                'created_at' => $paymentDate,
                'updated_at' => $paymentDate,
            ]);

            DB::table('payment_document_links')->insert([
                 'company_id' => $companyId,
                 'payment_id' => $paymentId,
                 'invoice_id' => $invoiceId, // Linking to invoice (if column exists, or use polymorphic)
                 // Note: based on ScenarioOneSeeder it seems there is a link table but maybe columns differ.
                 // ScenarioOneSeeder line 463 uses: payment_id, amount_applied. It doesn't show invoice_id column explicitly in insert but implicit link? 
                 // Wait, ScenarioOneSeeder 463: 'payment_document_links' -> payment_id, amount_applied. 
                 // It doesn't allow linking directly to invoice in that table based on that snippet?
                 // Let's check the schema if possible or just assume standard relation.
                 // Actually, usually these link tables have 'document_id' and 'document_type' OR specific columns.
                 // For now let's just create the Payment JE which is what drives the cash charts mostly.
                 'amount_applied' => $amount, 
                 'created_at' => $paymentDate,
                 'updated_at' => $paymentDate,
             ]);

             // Payment JE: Debit Bank, Credit AR
             $payJeHash = hash('sha256', 'pay_demo_'.Str::random(10));
             $payJeId = DB::table('journal_entries')->insertGetId([
                'company_id' => $companyId,
                'journal_id' => $bankJournalId,
                'currency_id' => $currencyId,
                'entry_date' => $paymentDate,
                'entry_number' => 'PAY/DEMO/' . Str::random(5),
                'reference' => 'Payment for ' . $invoiceId,
                'description' => 'Payment In',
                'created_by_user_id' => $userId,
                'is_posted' => true,
                'state' => 'posted',
                'total_debit' => $amount,
                'total_credit' => $amount,
                'hash' => $payJeHash,
                'source_type' => 'Kezi\Payment\Models\Payment',
                'source_id' => $paymentId,
                'created_at' => $paymentDate,
                'updated_at' => $paymentDate,
            ]);

            DB::table('journal_entry_lines')->insert([
                [
                    'company_id' => $companyId,
                    'journal_entry_id' => $payJeId,
                    'account_id' => $bankAccountId, // Bank gets money (Debit)
                    'currency_id' => $currencyId,
                    'original_currency_id' => $currencyId,
                    'debit' => $amount,
                    'credit' => 0,
                    'original_currency_amount' => $amount,
                    'exchange_rate_at_transaction' => 1,
                    'description' => 'Bank',
                    'created_at' => $paymentDate,
                    'updated_at' => $paymentDate,
                ],
                [
                    'company_id' => $companyId,
                    'journal_entry_id' => $payJeId,
                    'account_id' => $arAccountId, // Reduce AR (Credit)
                    'currency_id' => $currencyId,
                    'original_currency_id' => $currencyId,
                    'debit' => 0,
                    'credit' => $amount,
                    'original_currency_amount' => $amount,
                    'exchange_rate_at_transaction' => 1,
                    'description' => 'AR Clearing',
                    'created_at' => $paymentDate,
                    'updated_at' => $paymentDate,
                ]
            ]);
            
            DB::table('payments')->where('id', $paymentId)->update(['journal_entry_id' => $payJeId]);
        }
    }

    private function createBill($companyId, $currencyId, $userId, $vendorId, $monthDate, $journalId, $apAccountId, $expenseAccountId, $bankJournalId, $bankAccountId)
    {
        $date = $monthDate->copy()->addDays(rand(1, 28));
        $amount = rand(100000, 2000000); // Random amount between 100k and 2M IQD

        // 1. Vendor Bill
        $billId = DB::table('vendor_bills')->insertGetId([
            'company_id' => $companyId,
            'vendor_id' => $vendorId,
            'currency_id' => $currencyId,
            'bill_date' => $date,
            'accounting_date' => $date,
            'due_date' => $date->copy()->addDays(30),
            'bill_reference' => 'BILL-DEMO-' . Str::random(5),
            'total_amount' => $amount,
            'total_tax' => 0,
            'status' => 'posted',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        
        DB::table('vendor_bill_lines')->insert([
            'company_id' => $companyId,
            'vendor_bill_id' => $billId,
            'description' => 'Demo Expense',
            'quantity' => 1,
            'unit_price' => $amount,
            'subtotal' => $amount,
            'expense_account_id' => $expenseAccountId,
            'total_line_tax' => 0,
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        // 2. Bill Journal Entry
        $jeHash = hash('sha256', 'bill_demo_'.Str::random(10));
        $jeId = DB::table('journal_entries')->insertGetId([
            'company_id' => $companyId,
            'journal_id' => $journalId,
            'currency_id' => $currencyId,
            'entry_date' => $date,
            'entry_number' => 'BILL/DEMO/' . Str::random(5),
            'reference' => 'Demo Data',
            'description' => 'Demo Bill',
            'created_by_user_id' => $userId,
            'is_posted' => true,
            'state' => 'posted',
            'total_debit' => $amount,
            'total_credit' => $amount,
            'hash' => $jeHash,
            'source_type' => 'Kezi\Purchase\Models\VendorBill',
            'source_id' => $billId,
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        // Debit Expense, Credit AP
        DB::table('journal_entry_lines')->insert([
            [
                'company_id' => $companyId,
                'journal_entry_id' => $jeId,
                'account_id' => $expenseAccountId, // Expense (Debit)
                'currency_id' => $currencyId,
                'original_currency_id' => $currencyId,
                'debit' => $amount,
                'credit' => 0,
                'original_currency_amount' => $amount,
                'exchange_rate_at_transaction' => 1,
                'description' => 'Expense',
                'created_at' => $date,
                'updated_at' => $date,
            ],
            [
                'company_id' => $companyId,
                'journal_entry_id' => $jeId,
                'account_id' => $apAccountId, // AP (Credit)
                'currency_id' => $currencyId,
                'original_currency_id' => $currencyId,
                'debit' => 0,
                'credit' => $amount,
                'original_currency_amount' => $amount,
                'exchange_rate_at_transaction' => 1,
                'description' => 'AP',
                'created_at' => $date,
                'updated_at' => $date,
            ]
        ]);
        
        DB::table('vendor_bills')->where('id', $billId)->update(['journal_entry_id' => $jeId]);

        // 3. Payment (Randomly pay some bills)
        if (rand(0, 10) > 2) { 
            $paymentDate = $date->copy()->addDays(rand(1, 15));
            if ($paymentDate > Carbon::now()) $paymentDate = Carbon::now();

            $paymentId = DB::table('payments')->insertGetId([
                'company_id' => $companyId,
                'currency_id' => $currencyId,
                'payment_type' => 'Outbound', // Paying money
                'paid_to_from_partner_id' => $vendorId,
                'amount' => $amount,
                'payment_date' => $paymentDate,
                'journal_id' => $bankJournalId,
                'status' => 'Confirmed',
                'created_at' => $paymentDate,
                'updated_at' => $paymentDate,
            ]);
            
            DB::table('payment_document_links')->insert([
                 'company_id' => $companyId,
                 'payment_id' => $paymentId,
                 'amount_applied' => $amount, 
                 'created_at' => $paymentDate,
                 'updated_at' => $paymentDate,
             ]);

             // Payment JE: Debit AP, Credit Bank
             $payJeHash = hash('sha256', 'pay_out_demo_'.Str::random(10));
             $payJeId = DB::table('journal_entries')->insertGetId([
                'company_id' => $companyId,
                'journal_id' => $bankJournalId,
                'currency_id' => $currencyId,
                'entry_date' => $paymentDate,
                'entry_number' => 'PAY/OUT/DEMO/' . Str::random(5),
                'reference' => 'Payment for ' . $billId,
                'description' => 'Payment Out',
                'created_by_user_id' => $userId,
                'is_posted' => true,
                'state' => 'posted',
                'total_debit' => $amount,
                'total_credit' => $amount,
                'hash' => $payJeHash,
                'source_type' => 'Kezi\Payment\Models\Payment', // Or VendorPayment if separated
                'source_id' => $paymentId,
                'created_at' => $paymentDate,
                'updated_at' => $paymentDate,
            ]);

            DB::table('journal_entry_lines')->insert([
                [
                    'company_id' => $companyId,
                    'journal_entry_id' => $payJeId,
                    'account_id' => $apAccountId, // Reduce AP (Debit)
                    'currency_id' => $currencyId,
                    'original_currency_id' => $currencyId,
                    'debit' => $amount,
                    'credit' => 0,
                    'original_currency_amount' => $amount,
                    'exchange_rate_at_transaction' => 1,
                    'description' => 'AP Clearing',
                    'created_at' => $paymentDate,
                    'updated_at' => $paymentDate,
                ],
                [
                    'company_id' => $companyId,
                    'journal_entry_id' => $payJeId,
                    'account_id' => $bankAccountId, // Bank Pays (Credit)
                    'currency_id' => $currencyId,
                    'original_currency_id' => $currencyId,
                    'debit' => 0,
                    'credit' => $amount,
                    'original_currency_amount' => $amount,
                    'exchange_rate_at_transaction' => 1,
                    'description' => 'Bank',
                    'created_at' => $paymentDate,
                    'updated_at' => $paymentDate,
                ]
            ]);
            
            DB::table('payments')->where('id', $paymentId)->update(['journal_entry_id' => $payJeId]);
        }
    }
}
