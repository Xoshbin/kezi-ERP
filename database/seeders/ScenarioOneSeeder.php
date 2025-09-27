<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\VendorBill;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ScenarioOneSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();

        // Step 1.1: Create Currency (IQD)
        $iqd = DB::table('currencies')->insertGetId([
            'code' => 'IQD',
            'name' => 'Iraqi Dinar',
            'symbol' => 'ع.د',
            'exchange_rate' => 1.0,
            'decimal_places' => 3,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Step 1.2: Create Company
        $company = DB::table('companies')->insertGetId([
            'name' => 'Jmeryar Solutions',
            'address' => 'Slemani, Kurdistan Region, Iraq',
            'tax_id' => null,
            'currency_id' => $iqd,
            'fiscal_country' => 'IQ',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        (new UserSeeder)->run();

        // Step 1.3: Create User (Soran)
        $user = DB::table('users')->insertGetId([
            'company_id' => $company,
            'name' => 'Soran',
            'email' => 'soran@jmeryarerp.com',
            'password' => Hash::make('SecurePassword123!'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Step 2: Chart of Accounts
        $accounts = [
            ['code' => '1010', 'name' => 'Bank', 'type' => 'Asset'],
            ['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'Asset'],
            ['code' => '1500', 'name' => 'IT Equipment', 'type' => 'Asset'],
            ['code' => '1501', 'name' => 'Accumulated Depreciation', 'type' => 'Asset'],
            ['code' => '2100', 'name' => 'Accounts Payable', 'type' => 'Liability'],
            ['code' => '3000', 'name' => "Owner's Equity", 'type' => 'Equity'],
            ['code' => '4000', 'name' => 'Consulting Revenue', 'type' => 'Revenue'],
            ['code' => '5000', 'name' => 'Sales Discounts & Returns', 'type' => 'Revenue'],
            ['code' => '6100', 'name' => 'Depreciation Expense', 'type' => 'Expense'],
        ];
        $accountIds = [];
        foreach ($accounts as $acc) {
            $accountIds[$acc['code']] = DB::table('accounts')->insertGetId([
                'company_id' => $company,
                'code' => $acc['code'],
                'name' => $acc['name'],
                'type' => $acc['type'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Step 3.1: Journals
        $journals = [
            ['name' => 'Bank', 'type' => 'Bank', 'short_code' => 'BNK'],
            ['name' => 'Sales', 'type' => 'Sale', 'short_code' => 'INV'],
            ['name' => 'Purchases', 'type' => 'Purchase', 'short_code' => 'BILL'],
            ['name' => 'Miscellaneous', 'type' => 'Miscellaneous', 'short_code' => 'MISC'],
        ];
        $journalIds = [];
        foreach ($journals as $j) {
            $journalIds[$j['name']] = DB::table('journals')->insertGetId([
                'company_id' => $company,
                'name' => $j['name'],
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
            'reference' => 'Initial Capital Investment',
            'description' => "Soran's personal funds transferred to the Jmeryar Solutions bank account",
            'created_by_user_id' => $user,
            'is_posted' => false,
            'total_debit' => 15000000,
            'total_credit' => 15000000,
            'hash' => $jeHash,
            'previous_hash' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('journal_entry_lines')->insert([
            [
                'journal_entry_id' => $journalEntryId,
                'account_id' => $accountIds['1010'],
                'debit' => 15000000,
                'credit' => 0,
                'description' => 'Capital injection into company bank account',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'journal_entry_id' => $journalEntryId,
                'account_id' => $accountIds['3000'],
                'debit' => 0,
                'credit' => 15000000,
                'description' => "Owner's personal investment",
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Step 5.1: Create Vendor
        $paykarVendorId = DB::table('partners')->insertGetId([
            'company_id' => $company,
            'name' => 'Paykar Tech Supplies',
            'type' => 'Vendor',
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
            'status' => \Modules\Purchase\Models\VendorBill::STATUS_DRAFT,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('vendor_bill_lines')->insert([
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
            'reference' => 'Vendor Bill KE-LAPTOP-001',
            'description' => 'Purchase of laptop on credit',
            'created_by_user_id' => $user,
            'is_posted' => false,
            'currency_id' => $iqd,
            'total_debit' => 3000000,
            'total_credit' => 3000000,
            'hash' => $vbJeHash,
            'previous_hash' => $jeHash,
            'source_type' => 'App\Models\VendorBill',
            'source_id' => $vendorBillId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('journal_entry_lines')->insert([
            [
                'journal_entry_id' => $vendorBillJournalEntryId,
                'account_id' => $accountIds['1500'],
                'debit' => 3000000,
                'credit' => 0,
                'description' => 'Laptop purchase (fixed asset)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'journal_entry_id' => $vendorBillJournalEntryId,
                'account_id' => $accountIds['2100'],
                'debit' => 0,
                'credit' => 3000000,
                'description' => 'Accounts Payable for laptop',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('vendor_bills')->where('id', $vendorBillId)->update([
            'status' => \Modules\Purchase\Models\VendorBill::STATUS_DRAFT,
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
            'status' => \Modules\Sales\Models\Invoice::STATUS_DRAFT,
            'invoice_number' => 'INV-0001',
            'total_amount' => 5000000,
            'total_tax' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('invoice_lines')->insert([
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
            'reference' => 'Invoice INV-0001',
            'description' => 'IT setup services for Hawre Trading Group',
            'created_by_user_id' => $user,
            'is_posted' => false,
            'total_debit' => 5000000,
            'total_credit' => 5000000,
            'hash' => $invJeHash,
            'previous_hash' => $vbJeHash,
            'source_type' => 'App\Models\Invoice',
            'source_id' => $invoiceId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('journal_entry_lines')->insert([
            [
                'journal_entry_id' => $invoiceJournalEntryId,
                'account_id' => $accountIds['1200'],
                'debit' => 5000000,
                'credit' => 0,
                'description' => 'Accounts Receivable for IT services',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'journal_entry_id' => $invoiceJournalEntryId,
                'account_id' => $accountIds['4000'],
                'debit' => 0,
                'credit' => 5000000,
                'description' => 'Consulting Revenue',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('invoices')->where('id', $invoiceId)->update([
            'status' => \Modules\Sales\Models\Invoice::STATUS_DRAFT,
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
            'reference' => 'Payment from Hawre Trading Group',
            'description' => 'Full payment for invoice INV-0001',
            'created_by_user_id' => $user,
            'is_posted' => false,
            'total_debit' => 5000000,
            'total_credit' => 5000000,
            'hash' => $payJeHash,
            'previous_hash' => $invJeHash,
            'source_type' => 'App\Models\Payment',
            'source_id' => $paymentId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('journal_entry_lines')->insert([
            [
                'journal_entry_id' => $paymentJournalEntryId,
                'account_id' => $accountIds['1010'],
                'debit' => 5000000,
                'credit' => 0,
                'description' => 'Bank receipt from customer',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'journal_entry_id' => $paymentJournalEntryId,
                'account_id' => $accountIds['1200'],
                'debit' => 0,
                'credit' => 5000000,
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
            'status' => \Modules\Sales\Models\Invoice::STATUS_DRAFT,
        ]);
        DB::table('payment_document_links')->insert([
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
            'reference' => 'Payment to Paykar Tech Supplies',
            'description' => 'Payment for laptop purchase',
            'created_by_user_id' => $user,
            'is_posted' => false,
            'total_debit' => 3000000,
            'total_credit' => 3000000,
            'hash' => $vendorPayJeHash,
            'previous_hash' => $payJeHash,
            'source_type' => 'App\Models\Payment',
            'source_id' => $vendorPaymentId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('journal_entry_lines')->insert([
            [
                'journal_entry_id' => $vendorPaymentJournalEntryId,
                'account_id' => $accountIds['2100'],
                'debit' => 3000000,
                'credit' => 0,
                'description' => 'Clear Accounts Payable',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'journal_entry_id' => $vendorPaymentJournalEntryId,
                'account_id' => $accountIds['1010'],
                'debit' => 0,
                'credit' => 3000000,
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
            'status' => \Modules\Purchase\Models\VendorBill::STATUS_DRAFT,
        ]);
        DB::table('payment_document_links')->insert([
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
            'total_amount' => 500000,
            'total_tax' => 0,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('adjustment_document_lines')->insert([
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
            'reference' => 'Credit Note CN-0001',
            'description' => 'Refund to Hawre Trading Group',
            'created_by_user_id' => $user,
            'is_posted' => false,
            'total_debit' => 500000,
            'total_credit' => 500000,
            'hash' => $cnJeHash,
            'previous_hash' => $vendorPayJeHash,
            'source_type' => 'App\Models\AdjustmentDocument',
            'source_id' => $creditNoteId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('journal_entry_lines')->insert([
            [
                'journal_entry_id' => $creditNoteJournalEntryId,
                'account_id' => $accountIds['5000'],
                'debit' => 500000,
                'credit' => 0,
                'description' => 'Sales Discounts & Returns (refund)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'journal_entry_id' => $creditNoteJournalEntryId,
                'account_id' => $accountIds['1200'],
                'debit' => 0,
                'credit' => 500000,
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
