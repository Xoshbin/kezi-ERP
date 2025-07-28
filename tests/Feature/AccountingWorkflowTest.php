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
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class, \Tests\Traits\CreatesApplication::class);

test('the entire accounting workflow from setup to credit note', function () {
    // Step 1: Foundational Setup using the trait
    $company = $this->createConfiguredCompany();
    $user = User::factory()->for($company)->create();
    $this->actingAs($user);

    // Retrieve essentials from the configured company
    $currency = $company->currency;
    $currencyCode = $currency->code;
    $bankAccount = $company->defaultBankAccount;
    $arAccount = $company->defaultAccountsReceivable;
    $itEquipmentAccount = Account::factory()->for($company)->create(['type' => 'Fixed Asset']);
    $apAccount = $company->defaultAccountsPayable;
    $equityAccount = Account::factory()->for($company)->create(['type' => 'Equity']);
    $revenueAccount = Account::factory()->for($company)->create(['type' => 'Income']);
    $salesDiscountAccount = $company->defaultSalesDiscountAccount;
    $bankJournal = $company->defaultBankJournal;

    // MODIFIED: Define test-specific amounts using Money objects for precision
    $initialCapitalInvestment = Money::of(15_000_000, $currencyCode);
    $highEndLaptopCost = Money::of(3_000_000, $currencyCode);
    $itInfrastructureServiceCost = Money::of(5_000_000, $currencyCode);
    $goodwillDiscount = Money::of(500_000, $currencyCode);

    // Step 3: Capital Injection
    $journalEntryService = app(JournalEntryService::class);
    // MODIFIED: Use Money objects in the service call
    $capitalEntry = $journalEntryService->create([
        'company_id' => $company->id,
        'journal_id' => $bankJournal->id,
        'entry_date' => now()->toDateString(),
        'reference' => 'Initial Capital Investment',
        'lines' => [
            ['account_id' => $bankAccount->id, 'debit' => $initialCapitalInvestment, 'credit' => Money::of(0, $currencyCode)],
            ['account_id' => $equityAccount->id, 'debit' => Money::of(0, $currencyCode), 'credit' => $initialCapitalInvestment],
        ],
    ], true); // Post immediately

    $this->assertDatabaseHas('journal_entries', ['id' => $capitalEntry->id, 'is_posted' => true]);
    // MODIFIED: Assert against Money objects
    expect($capitalEntry->total_debit->isEqualTo($initialCapitalInvestment))->toBeTrue();
    expect($capitalEntry->total_credit->isEqualTo($initialCapitalInvestment))->toBeTrue();

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
                // MODIFIED: Use float value for unit price to pass validation
                'unit_price' => $highEndLaptopCost->getAmount()->toFloat(),
                'expense_account_id' => $itEquipmentAccount->id,
            ],
        ],
    ]);
    $vendorBillService->confirm($vendorBill, $user);

    $vendorBill->refresh();
    $purchaseEntry = $vendorBill->journalEntry;
    expect($purchaseEntry->reference)->toBe($vendorBill->bill_reference);
    expect($purchaseEntry->is_posted)->toBeTrue();
    // MODIFIED: Assert against Money objects and corrected amount
    expect($purchaseEntry->total_debit->isEqualTo($highEndLaptopCost))->toBeTrue();
    expect($purchaseEntry->lines->where('account_id', $itEquipmentAccount->id)->first()->debit->isEqualTo($highEndLaptopCost))->toBeTrue();
    expect($purchaseEntry->lines->where('account_id', $apAccount->id)->first()->credit->isEqualTo($highEndLaptopCost))->toBeTrue();

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
                // MODIFIED: Use float value for unit price to pass validation
                'unit_price' => $itInfrastructureServiceCost->getAmount()->toFloat(),
                'income_account_id' => $revenueAccount->id,
            ],
        ],
    ]);
    $invoiceService->confirm($invoice, $user);

    $invoice->refresh();
    $invoiceEntry = $invoice->journalEntry;
    expect($invoiceEntry->reference)->toBe($invoice->invoice_number);
    expect($invoiceEntry->is_posted)->toBeTrue();
    // MODIFIED: Assert against Money objects and corrected amount
    expect($invoiceEntry->total_debit->isEqualTo($itInfrastructureServiceCost))->toBeTrue();
    expect($invoiceEntry->lines->where('account_id', $arAccount->id)->first()->debit->isEqualTo($itInfrastructureServiceCost))->toBeTrue();
    expect($invoiceEntry->lines->where('account_id', $revenueAccount->id)->first()->credit->isEqualTo($itInfrastructureServiceCost))->toBeTrue();

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
                // MODIFIED: Use the major amount from the Money object
                'amount' => $itInfrastructureServiceCost->getAmount()->toFloat(),
            ],
        ],
    ], $user);
    $paymentService->confirm($customerPayment, $user);

    $customerPayment->refresh();
    $customerPaymentEntry = $customerPayment->journalEntry;
    expect($customerPaymentEntry->is_posted)->toBeTrue();
    // MODIFIED: Assert against Money objects
    expect($customerPaymentEntry->total_debit->isEqualTo($itInfrastructureServiceCost))->toBeTrue();
    expect($customerPaymentEntry->lines->where('account_id', $bankAccount->id)->first()->debit->isEqualTo($itInfrastructureServiceCost))->toBeTrue();
    expect($customerPaymentEntry->lines->where('account_id', $arAccount->id)->first()->credit->isEqualTo($itInfrastructureServiceCost))->toBeTrue();
    expect($invoice->fresh()->status)->toBe(Invoice::TYPE_PAID);

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
                // MODIFIED: Use the major amount from the Money object
                'amount' => $highEndLaptopCost->getAmount()->toFloat(),
            ],
        ],
    ], $user);
    $paymentService->confirm($vendorPayment, $user);

    $vendorPayment->refresh();
    $vendorPaymentEntry = $vendorPayment->journalEntry;
    expect($vendorPaymentEntry->is_posted)->toBeTrue();
    // MODIFIED: Assert against Money objects
    expect($vendorPaymentEntry->total_debit->isEqualTo($highEndLaptopCost))->toBeTrue();
    expect($vendorPaymentEntry->lines->where('account_id', $apAccount->id)->first()->debit->isEqualTo($highEndLaptopCost))->toBeTrue();
    expect($vendorPaymentEntry->lines->where('account_id', $bankAccount->id)->first()->credit->isEqualTo($highEndLaptopCost))->toBeTrue();
    expect($vendorBill->fresh()->status)->toBe(VendorBill::TYPE_PAID);

    // Step 8: Handling a Correction (Credit Note)
    $adjustmentService = app(AdjustmentDocumentService::class);

    $creditNote = \App\Models\AdjustmentDocument::factory()->create([
        'company_id' => $company->id,
        'reference_number' => 'CN-001',
        'total_tax' => Money::of(0, $currencyCode),
        'type' => 'Credit Note',
        'original_invoice_id' => $invoice->id,
        'date' => now()->toDateString(),
        'reason' => 'Goodwill discount for new client',
        // MODIFIED: Use Money object for total amount
        'total_amount' => $goodwillDiscount,
        'status' => 'Draft'
    ]);

    $adjustmentService->post($creditNote, $user);

    $creditNote->refresh();
    $creditNoteEntry = $creditNote->journalEntry;

    expect($creditNoteEntry->is_posted)->toBeTrue();
    // MODIFIED: Assert against Money objects
    expect($creditNoteEntry->total_debit->isEqualTo($goodwillDiscount))->toBeTrue();
    expect($creditNoteEntry->total_credit->isEqualTo($goodwillDiscount))->toBeTrue();

    // MODIFIED: Assert the debit using minor units for database check
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $creditNoteEntry->id,
        'account_id' => $salesDiscountAccount->id,
        'debit' => $goodwillDiscount->getMinorAmount()->toInt(),
    ]);

    // MODIFIED: Assert the credit using minor units for database check
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $creditNoteEntry->id,
        'account_id' => $arAccount->id,
        'credit' => $goodwillDiscount->getMinorAmount()->toInt(),
    ]);
});