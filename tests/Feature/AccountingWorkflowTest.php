<?php

use App\Models\Account;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Partner;
use App\Models\User;
use App\Models\VendorBill;
use App\Services\AdjustmentDocumentService;
use App\Services\InvoiceService;
use App\Services\JournalEntryService;
use App\Services\PaymentService;
use App\Services\VendorBillService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class, \Tests\Traits\CreatesApplication::class);

test('the entire accounting workflow from setup to credit note', function () {
    // Step 1: Foundational Setup using the trait
    $company = $this->createConfiguredCompany();
    $user = User::factory()->for($company)->create();
    $this->actingAs($user);

    // Retrieve essentials from the configured company
    $currency = $company->currency;
    $bankAccount = $company->defaultBankAccount;
    $arAccount = $company->defaultAccountsReceivable;
    $itEquipmentAccount = Account::factory()->for($company)->create(['type' => 'Fixed Asset']);
    $apAccount = $company->defaultAccountsPayable;
    $equityAccount = Account::factory()->for($company)->create(['type' => 'Equity']);
    $revenueAccount = Account::factory()->for($company)->create(['type' => 'Income']);
    $salesDiscountAccount = $company->defaultSalesDiscountAccount;
    $bankJournal = $company->defaultBankJournal;

    // Define test-specific amounts
    $initialCapitalInvestment = 15_000_000;
    $highEndLaptopCost = 3_000_000;
    $itInfrastructureServiceCost = 5_000_000;
    $goodwillDiscount = 500_000;

    // Step 3: Capital Injection
    $journalEntryService = app(JournalEntryService::class);
    $capitalEntry = $journalEntryService->create([
        'company_id' => $company->id,
        'journal_id' => $bankJournal->id,
        'entry_date' => now()->toDateString(),
        'reference' => 'Initial Capital Investment',
        'lines' => [
            ['account_id' => $bankAccount->id, 'debit' => $initialCapitalInvestment, 'credit' => 0],
            ['account_id' => $equityAccount->id, 'debit' => 0, 'credit' => $initialCapitalInvestment],
        ],
    ]);
    $journalEntryService->post($capitalEntry);

    $this->assertDatabaseHas('journal_entries', ['id' => $capitalEntry->id, 'is_posted' => true]);
    $this->assertEquals('15000000.00', $capitalEntry->total_debit);
    $this->assertEquals('15000000.00', $capitalEntry->total_credit);

    // Step 4: Purchasing a Fixed Asset
    $vendor = Partner::factory()->for($company)->create(['name' => 'Paykar Tech Supplies', 'type' => Partner::TYPE_VENDOR]);
    $vendorBillService = app(VendorBillService::class);
    $vendorBill = $vendorBillService->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'partner_id' => $vendor->id,
        'bill_date' => now()->toDateString(),
        'accounting_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'bill_reference' => 'KE-LAPTOP-001',
        'lines' => [
            [
                'description' => 'High-End Laptop for Business Use',
                'quantity' => 1,
                'unit_price' => $highEndLaptopCost,
                'expense_account_id' => $itEquipmentAccount->id,
            ],
        ],
    ], $user);
    $vendorBillService->confirm($vendorBill, $user);

    $vendorBill->refresh();
    $purchaseEntry = $vendorBill->journalEntry;
    expect($purchaseEntry->reference)->toBe($vendorBill->bill_reference);
    expect($purchaseEntry->is_posted)->toBeTrue();
    expect($purchaseEntry->total_debit)->toEqual(300000000);
    expect($purchaseEntry->lines->where('account_id', $itEquipmentAccount->id)->first()->debit)->toEqual(300000000);
    expect($purchaseEntry->lines->where('account_id', $apAccount->id)->first()->credit)->toEqual(300000000);

    // Step 5: Providing a Service & Invoicing
    $customer = Partner::factory()->for($company)->create(['name' => 'Hawre Trading Group', 'type' => Partner::TYPE_CUSTOMER]);
    $invoiceService = app(InvoiceService::class);
    $invoice = $invoiceService->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'currency_id' => $currency->id,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(15)->toDateString(),
        'lines' => [
            [
                'description' => 'On-site IT Infrastructure Setup',
                'quantity' => 1,
                'reference' => 'IT-SETUP-001',
                'unit_price' => 5000000,
                'income_account_id' => $revenueAccount->id,
            ],
        ],
    ]);
    $invoiceService->confirm($invoice, $user);

    $invoice->refresh();
    $invoiceEntry = $invoice->journalEntry;
    expect($invoiceEntry->reference)->toBe($invoice->invoice_number);
    expect($invoiceEntry->is_posted)->toBeTrue();
    expect($invoiceEntry->total_debit)->toEqual(500000000);
    expect($invoiceEntry->lines->where('account_id', $arAccount->id)->first()->debit)->toEqual(500000000);
    expect($invoiceEntry->lines->where('account_id', $revenueAccount->id)->first()->credit)->toEqual(500000000);

    // Step 6: Receiving Payment from Customer
    $paymentService = app(PaymentService::class);
    $customerPayment = $paymentService->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'paid_to_from_partner_id' => $customer->id,
        'payment_date' => now()->toDateString(),
        'journal_id' => $bankJournal->id,
        'documents' => [
            [
                'document_id' => $invoice->id,
                'document_type' => 'invoice',
                'amount' => 5000000,
            ],
        ],
    ], $user);
    $paymentService->confirm($customerPayment, $user);

    $customerPayment->refresh();
    $customerPaymentEntry = $customerPayment->journalEntry;
    expect($customerPaymentEntry->is_posted)->toBeTrue();
    expect($customerPaymentEntry->total_debit)->toEqual(5000000);
    expect($customerPaymentEntry->lines->where('account_id', $bankAccount->id)->first()->debit)->toEqual(5000000);
    expect($customerPaymentEntry->lines->where('account_id', $arAccount->id)->first()->credit)->toEqual(5000000);
    expect($invoice->fresh()->status)->toBe(Invoice::TYPE_POSTED);

    // Step 7: Paying a Vendor
    $vendorPayment = $paymentService->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'paid_to_from_partner_id' => $vendor->id,
        'payment_date' => now()->toDateString(),
        'journal_id' => $bankJournal->id,
        'documents' => [
            [
                'document_id' => $vendorBill->id,
                'document_type' => 'vendor_bill',
                'amount' => 3000000,
            ],
        ],
    ], $user);
    $paymentService->confirm($vendorPayment, $user);

    $vendorPayment->refresh();
    $vendorPaymentEntry = $vendorPayment->journalEntry;
    expect($vendorPaymentEntry->is_posted)->toBeTrue();
    expect($vendorPaymentEntry->total_debit)->toEqual(3000000.0);
    expect($vendorPaymentEntry->lines->where('account_id', $apAccount->id)->first()->debit)->toEqual(3000000.0);
    expect($vendorPaymentEntry->lines->where('account_id', $bankAccount->id)->first()->credit)->toEqual(3000000.0);
    expect($vendorBill->fresh()->status)->toBe(VendorBill::TYPE_POSTED);

    // Step 8: Handling a Correction (Credit Note)
    $adjustmentService = app(AdjustmentDocumentService::class);

    // The AdjustmentDocument model *itself* does not have a 'lines' relationship.
    // Instead, you create the header document with its total_amount,
    // and the AdjustmentDocumentService::post() method is responsible
    // for generating the correct JournalEntry and its JournalEntryLine records.

    $creditNote = \App\Models\AdjustmentDocument::factory()->create([
        'company_id' => $company->id,
        'reference_number' => 'CN-001',
        // The total_tax and total_amount fields on AdjustmentDocument represent the overall adjustment.
        // The detailed debit/credit to specific accounts (like Sales Discounts & Returns)
        // are handled by the service when it generates the JournalEntryLines.
        'total_tax' => 0,
        'type' => 'Credit Note',
        'original_invoice_id' => $invoice->id, // Link to the original invoice
        'date' => now()->toDateString(),
        'reason' => 'Goodwill discount for new client',
        'total_amount' => $goodwillDiscount, // Total amount of the credit note
        'status' => 'Draft'
    ]);

    // Your AdjustmentDocumentService::post() method should contain the logic
    // to create the JournalEntry and its JournalEntryLines based on the
    // Credit Note's type, amount, and the accounts configured in your system (e.g., config defaults).
    // For a credit note, it would typically debit a 'Sales Discounts & Returns' (or similar) account
    // and credit 'Accounts Receivable'.
    $adjustmentService->post($creditNote, $user);

    $creditNote->refresh();
    $creditNoteEntry = $creditNote->journalEntry;

    // Assertions will now check the JournalEntry and its lines, not direct AdjustmentDocument lines.
    expect($creditNoteEntry->is_posted)->toBeTrue();
    expect($creditNoteEntry->total_debit)->toEqual('500000.00');
    expect($creditNoteEntry->total_credit)->toEqual('500000.00');

    // Assert the debit to Sales Discounts & Returns (Contra-Revenue)
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $creditNoteEntry->id,
        'account_id' => $salesDiscountAccount->id,
        'debit' => $goodwillDiscount,
    ]);

    // Assert the credit to Accounts Receivable (Asset)
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $creditNoteEntry->id,
        'account_id' => $arAccount->id,
        'credit' => $goodwillDiscount,
    ]);
});