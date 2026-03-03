<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ScenarioOneSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();

        // Step 1: Currency (IQD)
        $iqd = DB::table('currencies')->where('code', 'IQD')->value('id');
        if (! $iqd) {
            $iqd = DB::table('currencies')->insertGetId([
                'code' => 'IQD',
                'name' => json_encode(['en' => 'Iraqi Dinar']),
                'symbol' => 'ع.د',
                'decimal_places' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $usd = DB::table('currencies')->where('code', 'USD')->value('id');
        if (! $usd) {
            $usd = DB::table('currencies')->insertGetId([
                'code' => 'USD',
                'name' => json_encode(['en' => 'US Dollar']),
                'symbol' => '$',
                'decimal_places' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Step 1.1: Create Company
        $company = DB::table('companies')->insertGetId([
            'name' => 'Kezi Solutions',
            'address' => 'Slemani, Kurdistan Region, Iraq',
            'tax_id' => null,
            'currency_id' => $iqd,
            'fiscal_country' => 'IQ',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert exchange rate (Now that we have company_id)
        DB::table('currency_rates')->insert([
            'company_id' => $company,
            'currency_id' => $iqd,
            'rate' => 1.0,
            'effective_date' => now()->startOfDay(),
            'source' => 'Manual',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        (new UserSeeder)->run();
        (new \Kezi\Foundation\Database\Seeders\RolesAndPermissionsSeeder)->run();

        // Step 1.3: Create User (Soran)
        $user = DB::table('users')->updateOrInsert(
            ['email' => 'soran@kezierp.com'],
            [
                'name' => 'Soran',
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        // updateOrInsert returns boolean, we need ID.
        $userId = DB::table('users')->where('email', 'soran@kezierp.com')->value('id');

        // Attach user to company
        DB::table('company_user')->updateOrInsert(
            ['company_id' => $company, 'user_id' => $userId],
            ['created_at' => now(), 'updated_at' => now()]
        );

        // Ensure super_admin role exists for THIS company and assign to Soran
        $role = \Spatie\Permission\Models\Role::firstOrCreate(
            ['name' => 'super_admin', 'company_id' => $company],
            ['guard_name' => 'web']
        );
        $role->givePermissionTo(\Spatie\Permission\Models\Permission::all());

        DB::table('model_has_roles')->updateOrInsert(
            [
                'role_id' => $role->id,
                'model_type' => 'App\Models\User',
                'model_id' => $userId,
                'company_id' => $company,
            ],
            []
        );

        $user = $userId; // Keep $user variable for later use

        // Step 2: Chart of Accounts
        $accounts = [
            ['code' => '1010', 'name' => 'Bank', 'type' => 'bank_and_cash'],
            ['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'receivable'],
            ['code' => '1500', 'name' => 'IT Equipment', 'type' => 'fixed_assets'],
            ['code' => '1501', 'name' => 'Accumulated Depreciation', 'type' => 'fixed_assets'],
            ['code' => '2100', 'name' => 'Accounts Payable', 'type' => 'payable'],
            ['code' => '3000', 'name' => "Owner's Equity", 'type' => 'equity'],
            ['code' => '4000', 'name' => 'Consulting Revenue', 'type' => 'income'],
            ['code' => '5000', 'name' => 'Sales Discounts & Returns', 'type' => 'income'],
            ['code' => '6100', 'name' => 'Depreciation Expense', 'type' => 'depreciation'],
            ['code' => '220150', 'name' => 'Withholding Tax Payable', 'type' => 'current_liabilities'],
        ];
        $accountIds = [];
        foreach ($accounts as $acc) {
            $accountIds[$acc['code']] = DB::table('accounts')->insertGetId([
                'company_id' => $company,
                'code' => $acc['code'],
                'name' => json_encode(['en' => $acc['name']]),
                'type' => $acc['type'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Step 3.1: Journals
        $journals = [
            ['name' => 'Bank', 'type' => 'bank', 'short_code' => 'BNK'],
            ['name' => 'Sales', 'type' => 'sale', 'short_code' => 'INV'],
            ['name' => 'Purchases', 'type' => 'purchase', 'short_code' => 'BILL'],
            ['name' => 'Miscellaneous', 'type' => 'miscellaneous', 'short_code' => 'MISC'],
        ];
        $journalIds = [];
        foreach ($journals as $j) {
            $journalIds[$j['name']] = DB::table('journals')->insertGetId([
                'company_id' => $company,
                'name' => json_encode(['en' => $j['name']]),
                'type' => $j['type'],
                'short_code' => $j['short_code'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Step 3.2: Update Company with Default Accounts/Journals
        DB::table('companies')->where('id', $company)->update([
            'default_bank_account_id' => $accountIds['1010'],
            'default_accounts_receivable_id' => $accountIds['1200'],
            'default_accounts_payable_id' => $accountIds['2100'],
            'default_sales_discount_account_id' => $accountIds['5000'],
            'default_bank_journal_id' => $journalIds['Bank'],
            'default_sales_journal_id' => $journalIds['Sales'],
            'default_purchase_journal_id' => $journalIds['Purchases'],
            // The following fields are left null or not set as they are not available in your seed data:
            'default_tax_receivable_id' => null,
            'default_tax_account_id' => null,
            'default_depreciation_journal_id' => null,
            'default_outstanding_receipts_account_id' => null,
            'parent_company_id' => null,
            'updated_at' => now(),
        ]);

        // Step 4: Capital Injection (Manual Journal Entry)
        $jeHash = hash('sha256', 'capital_injection_'.now());
        $journalEntryId = DB::table('journal_entries')->insertGetId([
            'company_id' => $company,
            'journal_id' => $journalIds['Bank'],
            'currency_id' => $iqd,
            'entry_date' => now(),
            'entry_number' => 'DRAFT/2026/0001',
            'reference' => 'Initial Capital Investment',
            'description' => "Soran's personal funds transferred to the Kezi Solutions bank account",
            'created_by_user_id' => $user,
            'is_posted' => false,
            'state' => 'draft',
            'total_debit' => 15000000,
            'total_credit' => 15000000,
            'hash' => $jeHash,
            'previous_hash' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('journal_entry_lines')->insert([
            [
                'company_id' => $company,
                'journal_entry_id' => $journalEntryId,
                'account_id' => $accountIds['1010'],
                'currency_id' => $iqd,
                'original_currency_id' => $iqd,
                'debit' => 15000000,
                'credit' => 0,
                'original_currency_amount' => 15000000,
                'exchange_rate_at_transaction' => 1,
                'description' => 'Capital injection into company bank account',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $company,
                'journal_entry_id' => $journalEntryId,
                'account_id' => $accountIds['3000'],
                'currency_id' => $iqd,
                'original_currency_id' => $iqd,
                'debit' => 0,
                'credit' => 15000000,
                'original_currency_amount' => 15000000,
                'exchange_rate_at_transaction' => 1,
                'description' => "Owner's personal investment",
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Step 5.0: Call WHT Seeder
        (new \Kezi\Accounting\Database\Seeders\WithholdingTaxTypeSeeder)->run();
        $whtTypeId = DB::table('withholding_tax_types')->where('name', 'like', '%Services%')->value('id');

        // Step 5.1: Create Vendor
        $paykarVendorId = DB::table('partners')->insertGetId([
            'company_id' => $company,
            'name' => 'Paykar Tech Supplies',
            'type' => 'Vendor',
            'withholding_tax_type_id' => $whtTypeId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Step 5.2: Vendor Bill
        $vendorBillId = DB::table('vendor_bills')->insertGetId([
            'company_id' => $company,
            'vendor_id' => $paykarVendorId,
            'currency_id' => $iqd,
            'bill_date' => now(),
            'accounting_date' => now(),
            'due_date' => Carbon::now()->addDays(30),
            'bill_reference' => 'KE-LAPTOP-001',
            'total_amount' => 3000000,
            'total_tax' => 0,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('vendor_bill_lines')->insert([
            'company_id' => $company,
            'vendor_bill_id' => $vendorBillId,
            'description' => 'High-End Laptop for Business Use',
            'quantity' => 1,
            'unit_price' => 3000000,
            'subtotal' => 3000000,
            'expense_account_id' => $accountIds['1500'],
            'total_line_tax' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $vbJeHash = hash('sha256', 'vendor_bill_'.now());
        $vendorBillJournalEntryId = DB::table('journal_entries')->insertGetId([
            'company_id' => $company,
            'journal_id' => $journalIds['Purchases'],
            'entry_date' => now(),
            'entry_number' => 'DRAFT/2026/0002',
            'reference' => 'Vendor Bill KE-LAPTOP-001',
            'description' => 'Purchase of laptop on credit',
            'created_by_user_id' => $user,
            'is_posted' => false,
            'state' => 'draft',
            'currency_id' => $iqd,
            'total_debit' => 3000000,
            'total_credit' => 3000000,
            'hash' => $vbJeHash,
            'previous_hash' => $jeHash,
            'source_type' => 'Kezi\Purchase\Models\VendorBill',
            'source_id' => $vendorBillId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('journal_entry_lines')->insert([
            [
                'company_id' => $company,
                'journal_entry_id' => $vendorBillJournalEntryId,
                'account_id' => $accountIds['1500'],
                'currency_id' => $iqd,
                'original_currency_id' => $iqd,
                'debit' => 3000000,
                'credit' => 0,
                'original_currency_amount' => 3000000,
                'exchange_rate_at_transaction' => 1,
                'description' => 'Laptop purchase (fixed asset)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $company,
                'journal_entry_id' => $vendorBillJournalEntryId,
                'account_id' => $accountIds['2100'],
                'currency_id' => $iqd,
                'original_currency_id' => $iqd,
                'debit' => 0,
                'credit' => 3000000,
                'original_currency_amount' => 3000000,
                'exchange_rate_at_transaction' => 1,
                'description' => 'Accounts Payable for laptop',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('vendor_bills')->where('id', $vendorBillId)->update([
            'status' => 'draft',
            'journal_entry_id' => $vendorBillJournalEntryId,
        ]);

        // Step 6.1: Create Customer
        $hawreCustomerId = DB::table('partners')->insertGetId([
            'company_id' => $company,
            'name' => 'Hawre Trading Group',
            'type' => 'Customer',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Step 6.2: Customer Invoice
        $invoiceId = DB::table('invoices')->insertGetId([
            'company_id' => $company,
            'currency_id' => $iqd,
            'customer_id' => $hawreCustomerId,
            'invoice_date' => now(),
            'due_date' => Carbon::now()->addDays(15),
            'status' => 'draft',
            'invoice_number' => 'INV-0001',
            'total_amount' => 5000000,
            'total_tax' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('invoice_lines')->insert([
            'company_id' => $company,
            'invoice_id' => $invoiceId,
            'description' => 'On-site IT Infrastructure Setup',
            'quantity' => 1,
            'unit_price' => 5000000,
            'subtotal' => 5000000,
            'income_account_id' => $accountIds['4000'],
            'total_line_tax' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $invJeHash = hash('sha256', 'invoice_'.now());
        $invoiceJournalEntryId = DB::table('journal_entries')->insertGetId([
            'company_id' => $company,
            'journal_id' => $journalIds['Sales'],
            'currency_id' => $iqd,
            'entry_date' => now(),
            'entry_number' => 'DRAFT/2026/0003',
            'reference' => 'Invoice INV-0001',
            'description' => 'IT setup services for Hawre Trading Group',
            'created_by_user_id' => $user,
            'is_posted' => false,
            'state' => 'draft',
            'total_debit' => 5000000,
            'total_credit' => 5000000,
            'hash' => $invJeHash,
            'previous_hash' => $vbJeHash,
            'source_type' => 'Kezi\Sales\Models\Invoice',
            'source_id' => $invoiceId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('journal_entry_lines')->insert([
            [
                'company_id' => $company,
                'journal_entry_id' => $invoiceJournalEntryId,
                'account_id' => $accountIds['1200'],
                'currency_id' => $iqd,
                'original_currency_id' => $iqd,
                'debit' => 5000000,
                'credit' => 0,
                'original_currency_amount' => 5000000,
                'exchange_rate_at_transaction' => 1,
                'description' => 'Accounts Receivable for IT services',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $company,
                'journal_entry_id' => $invoiceJournalEntryId,
                'account_id' => $accountIds['4000'],
                'currency_id' => $iqd,
                'original_currency_id' => $iqd,
                'debit' => 0,
                'credit' => 5000000,
                'original_currency_amount' => 5000000,
                'exchange_rate_at_transaction' => 1,
                'description' => 'Consulting Revenue',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('invoices')->where('id', $invoiceId)->update([
            'status' => 'draft',
            'invoice_number' => 'INV-0001',
            'journal_entry_id' => $invoiceJournalEntryId,
        ]);

        // Step 7: Receive Payment from Customer
        $paymentId = DB::table('payments')->insertGetId([
            'company_id' => $company,
            'currency_id' => $iqd,
            'payment_type' => 'Inbound',
            'paid_to_from_partner_id' => $hawreCustomerId,
            'amount' => 5000000,
            'payment_date' => now(),
            'journal_id' => $journalIds['Bank'],
            'status' => 'Confirmed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $payJeHash = hash('sha256', 'payment_'.now());
        $paymentJournalEntryId = DB::table('journal_entries')->insertGetId([
            'company_id' => $company,
            'journal_id' => $journalIds['Bank'],
            'currency_id' => $iqd,
            'entry_date' => now(),
            'entry_number' => 'DRAFT/2026/0004',
            'reference' => 'Payment from Hawre Trading Group',
            'description' => 'Full payment for invoice INV-0001',
            'created_by_user_id' => $user,
            'is_posted' => false,
            'state' => 'draft',
            'total_debit' => 5000000,
            'total_credit' => 5000000,
            'hash' => $payJeHash,
            'previous_hash' => $invJeHash,
            'source_type' => 'Kezi\Payment\Models\Payment',
            'source_id' => $paymentId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('journal_entry_lines')->insert([
            [
                'company_id' => $company,
                'journal_entry_id' => $paymentJournalEntryId,
                'account_id' => $accountIds['1010'],
                'currency_id' => $iqd,
                'original_currency_id' => $iqd,
                'debit' => 5000000,
                'credit' => 0,
                'original_currency_amount' => 5000000,
                'exchange_rate_at_transaction' => 1,
                'description' => 'Bank receipt from customer',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $company,
                'journal_entry_id' => $paymentJournalEntryId,
                'account_id' => $accountIds['1200'],
                'currency_id' => $iqd,
                'original_currency_id' => $iqd,
                'debit' => 0,
                'credit' => 5000000,
                'original_currency_amount' => 5000000,
                'exchange_rate_at_transaction' => 1,
                'description' => 'Clear Accounts Receivable',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('payments')->where('id', $paymentId)->update([
            'status' => 'Confirmed',
            'journal_entry_id' => $paymentJournalEntryId,
        ]);
        DB::table('invoices')->where('id', $invoiceId)->update([
            'status' => 'draft',
        ]);
        DB::table('payment_document_links')->insert([
            'company_id' => $company,
            'payment_id' => $paymentId,
            'amount_applied' => 5000000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Step 8: Pay Vendor
        $vendorPaymentId = DB::table('payments')->insertGetId([
            'company_id' => $company,
            'currency_id' => $iqd,
            'payment_type' => 'Outbound',
            'paid_to_from_partner_id' => $paykarVendorId,
            'amount' => 3000000,
            'payment_date' => now(),
            'journal_id' => $journalIds['Bank'],
            'status' => 'Confirmed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $vendorPayJeHash = hash('sha256', 'vendor_payment_'.now());
        $vendorPaymentJournalEntryId = DB::table('journal_entries')->insertGetId([
            'company_id' => $company,
            'currency_id' => $iqd,
            'journal_id' => $journalIds['Bank'],
            'entry_date' => now(),
            'entry_number' => 'DRAFT/2026/0005',
            'reference' => 'Payment to Paykar Tech Supplies',
            'description' => 'Payment for laptop purchase',
            'created_by_user_id' => $user,
            'is_posted' => false,
            'state' => 'draft',
            'total_debit' => 3000000,
            'total_credit' => 3000000,
            'hash' => $vendorPayJeHash,
            'previous_hash' => $payJeHash,
            'source_type' => 'Kezi\Payment\Models\Payment',
            'source_id' => $vendorPaymentId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('journal_entry_lines')->insert([
            [
                'company_id' => $company,
                'journal_entry_id' => $vendorPaymentJournalEntryId,
                'account_id' => $accountIds['2100'],
                'currency_id' => $iqd,
                'original_currency_id' => $iqd,
                'debit' => 3000000,
                'credit' => 0,
                'original_currency_amount' => 3000000,
                'exchange_rate_at_transaction' => 1,
                'description' => 'Clear Accounts Payable',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $company,
                'journal_entry_id' => $vendorPaymentJournalEntryId,
                'account_id' => $accountIds['1010'],
                'currency_id' => $iqd,
                'original_currency_id' => $iqd,
                'debit' => 0,
                'credit' => 3000000,
                'original_currency_amount' => 3000000,
                'exchange_rate_at_transaction' => 1,
                'description' => 'Bank payment to vendor',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('payments')->where('id', $vendorPaymentId)->update([
            'status' => 'Confirmed',
            'journal_entry_id' => $vendorPaymentJournalEntryId,
        ]);
        DB::table('vendor_bills')->where('id', $vendorBillId)->update([
            'status' => 'draft',
        ]);
        DB::table('payment_document_links')->insert([
            'company_id' => $company,
            'payment_id' => $vendorPaymentId,
            'amount_applied' => 3000000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Step 9: Credit Note (Adjustment Document)
        $creditNoteId = DB::table('adjustment_documents')->insertGetId([
            'company_id' => $company,
            'type' => 'Credit Note',
            'currency_id' => $iqd,
            'original_invoice_id' => $invoiceId,
            'date' => now(),
            'reference_number' => 'CN-0001',
            'reason' => 'Goodwill discount for new client',
            'subtotal' => 500000,
            'total_amount' => 500000,
            'total_tax' => 0,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('adjustment_document_lines')->insert([
            'company_id' => $company,
            'adjustment_document_id' => $creditNoteId,
            'description' => 'Refund for IT Setup Services',
            'quantity' => 1,
            'unit_price' => 500000,
            'subtotal' => 500000,
            'account_id' => $accountIds['5000'], // Sales Discounts & Returns
            'total_line_tax' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $cnJeHash = hash('sha256', 'credit_note_'.now());
        $creditNoteJournalEntryId = DB::table('journal_entries')->insertGetId([
            'company_id' => $company,
            'currency_id' => $iqd,
            'journal_id' => $journalIds['Sales'],
            'entry_date' => now(),
            'entry_number' => 'DRAFT/2026/0006',
            'reference' => 'Credit Note CN-0001',
            'description' => 'Refund to Hawre Trading Group',
            'created_by_user_id' => $user,
            'is_posted' => false,
            'state' => 'draft',
            'total_debit' => 500000,
            'total_credit' => 500000,
            'hash' => $cnJeHash,
            'previous_hash' => $vendorPayJeHash,
            'source_type' => 'Kezi\Accounting\Models\AdjustmentDocument',
            'source_id' => $creditNoteId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('journal_entry_lines')->insert([
            [
                'company_id' => $company,
                'journal_entry_id' => $creditNoteJournalEntryId,
                'account_id' => $accountIds['5000'],
                'currency_id' => $iqd,
                'original_currency_id' => $iqd,
                'debit' => 500000,
                'credit' => 0,
                'original_currency_amount' => 500000,
                'exchange_rate_at_transaction' => 1,
                'description' => 'Sales Discounts & Returns (refund)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $company,
                'journal_entry_id' => $creditNoteJournalEntryId,
                'account_id' => $accountIds['1200'],
                'currency_id' => $iqd,
                'original_currency_id' => $iqd,
                'debit' => 0,
                'credit' => 500000,
                'original_currency_amount' => 500000,
                'exchange_rate_at_transaction' => 1,
                'description' => 'Reduce Accounts Receivable',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('adjustment_documents')->where('id', $creditNoteId)->update([
            'status' => 'draft',
            'journal_entry_id' => $creditNoteJournalEntryId,
        ]);

        DB::commit();
    }
}
