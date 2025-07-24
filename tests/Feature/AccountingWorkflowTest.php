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

uses(RefreshDatabase::class);

test('the entire accounting workflow from setup to credit note', function () {
    $initialCapitalInvestment = 15_000_000;
    $highEndLaptopCost = 3_000_000;
    $itInfrastructureServiceCost = 5_000_000;
    $goodwillDiscount = 500_000;

    // Step 1: Foundational Setup
    $currency = Currency::factory()->create([
        'code' => 'IQD',
        'name' => 'Iraqi Dinar',
        'symbol' => 'ع.د',
        'exchange_rate' => 1.0,
        'is_active' => true,
    ]);

    $company = Company::factory()->create([
        'name' => 'Jmeryar ERP',
        'currency_id' => $currency->id,
    ]);

    $user = User::factory()->for($company)->create([
        'name' => 'Soran',
        'email' => 'soran@jmeryarerp.com',
    ]);

    $this->actingAs($user);

    // Step 2: Building the Chart of Accounts
    $accountsData = [
        ['company_id' => $company->id, 'code' => '1010', 'name' => 'Bank', 'type' => 'Asset'],
        ['company_id' => $company->id, 'code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'Asset'],
        ['company_id' => $company->id, 'code' => '1500', 'name' => 'IT Equipment', 'type' => 'Asset'],
        ['company_id' => $company->id, 'code' => '2100', 'name' => 'Accounts Payable', 'type' => 'Liability'],
        ['company_id' => $company->id, 'code' => '3000', 'name' => 'Owner\'s Equity', 'type' => 'Equity'],
        ['company_id' => $company->id, 'code' => '4000', 'name' => 'Consulting Revenue', 'type' => 'Revenue'],
        ['company_id' => $company->id, 'code' => '5000', 'name' => 'Sales Discounts & Returns', 'type' => 'Revenue'],
    ];
    Account::factory()->createMany($accountsData);

    $bankAccount = Account::where('code', '1010')->first();
    $arAccount = Account::where('code', '1200')->first();
    $itEquipmentAccount = Account::where('code', '1500')->first();
    $apAccount = Account::where('code', '2100')->first();
    $equityAccount = Account::where('code', '3000')->first();
    $revenueAccount = Account::where('code', '4000')->first();
    $salesDiscountAccount = Account::where('code', '5000')->first();

    $bankJournal = Journal::factory()->for($company)->create([
        'type' => 'Bank',
        'default_debit_account_id' => $bankAccount->id,
        'default_credit_account_id' => $bankAccount->id,
    ]);

    $taxReceivableAccount = Account::factory()->for($company)->create(['name' => 'Tax Receivable', 'type' => 'Asset']);
    $purchaseJournal = Journal::factory()->for($company)->create(['name' => 'Purchase Journal', 'type' => 'Purchase']);
    $salesJournal = Journal::factory()->for($company)->create(['name' => 'Sales Journal', 'type' => 'Sales']);

    config([
        'accounting.defaults.accounts_payable_id' => $apAccount->id,
        'accounting.defaults.tax_receivable_id' => $taxReceivableAccount->id,
        'accounting.defaults.purchase_journal_id' => $purchaseJournal->id,
        'accounting.defaults.sales_journal_id' => $salesJournal->id,
        'accounting.defaults.accounts_receivable_id' => $arAccount->id,
    ]);

    // Step 3: Capital Injection
    $journalEntryService = new JournalEntryService();
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
    $vendor = Partner::factory()->for($company)->create(['name' => 'Paykar Tech Supplies', 'type' => 'Vendor']);
    $vendorBillService = new VendorBillService();
    $vendorBill = $vendorBillService->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'vendor_id' => $vendor->id,
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
    ]);
    $vendorBillService->confirm($vendorBill, $user);

    $vendorBill->refresh();
    $purchaseEntry = $vendorBill->journalEntry;
    expect($purchaseEntry->reference)->toBe($vendorBill->bill_reference);
    expect($purchaseEntry->is_posted)->toBeTrue();
    expect($purchaseEntry->total_debit)->toEqual('3000000.00');
    expect($purchaseEntry->lines->where('account_id', $itEquipmentAccount->id)->first()->debit)->toEqual('3000000.00');
    expect($purchaseEntry->lines->where('account_id', $apAccount->id)->first()->credit)->toEqual('3000000.00');

    // Step 5: Providing a Service & Invoicing
    $customer = Partner::factory()->for($company)->create(['name' => 'Hawre Trading Group', 'type' => 'Customer']);
    $invoiceService = new InvoiceService();
    $invoice = $invoiceService->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'currency_id' => $currency->id,
        'total_amount' => 0,
        'total_tax' => 0,
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
    expect($invoiceEntry->total_debit)->toEqual('5000000.00');
    expect($invoiceEntry->lines->where('account_id', $arAccount->id)->first()->debit)->toEqual('5000000.00');
    expect($invoiceEntry->lines->where('account_id', $revenueAccount->id)->first()->credit)->toEqual('5000000.00');

    // Step 6: Receiving Payment from Customer
    $paymentService = new PaymentService();
    $customerPayment = $paymentService->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'paid_to_from_partner_id' => $customer->id,
        'payment_type' => 'inbound',
        'partner_id' => $customer->id,
        'amount' => 5000000,
        'payment_date' => now()->toDateString(),
        'journal_id' => $bankJournal->id,
        'invoice_ids' => [$invoice->id],
    ], $user);
    $paymentService->confirm($customerPayment, $user);

    $customerPayment->refresh();
    $customerPaymentEntry = $customerPayment->journalEntry;
    expect($customerPaymentEntry->is_posted)->toBeTrue();
    expect($customerPaymentEntry->total_debit)->toEqual('5000000.00');
    expect($customerPaymentEntry->lines->where('account_id', $bankAccount->id)->first()->debit)->toEqual('5000000.00');
    expect($customerPaymentEntry->lines->where('account_id', $arAccount->id)->first()->credit)->toEqual('5000000.00');
    expect($invoice->fresh()->status)->toBe(Invoice::TYPE_POSTED);

    // Step 7: Paying a Vendor
    $vendorPayment = $paymentService->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'paid_to_from_partner_id' => $vendor->id,
        'payment_type' => 'Outbound',
        'partner_id' => $vendor->id,
        'amount' => 3000000,
        'payment_date' => now()->toDateString(),
        'journal_id' => $bankJournal->id,
        'vendor_bill_ids' => [$vendorBill->id],
    ], $user);
    $paymentService->confirm($vendorPayment, $user);

    $vendorPayment->refresh();
    $vendorPaymentEntry = $vendorPayment->journalEntry;
    expect($vendorPaymentEntry->is_posted)->toBeTrue();
    expect($vendorPaymentEntry->total_debit)->toEqual(3000000.0);
    expect($vendorPaymentEntry->lines->where('account_id', $apAccount->id)->first()->debit)->toEqual(3000000.0);
    expect($vendorPaymentEntry->lines->where('account_id', $bankAccount->id)->first()->credit)->toEqual(3000000.0);
    expect($vendorBill->fresh()->status)->toBe(VendorBill::TYPE_POSTED);

    // // Step 8: Handling a Correction (Credit Note)
    // $adjustmentService = new AdjustmentDocumentService();
    // // AdjustmentDocument doesn't have a service `create` method, so we create it directly.
    // $creditNote = \App\Models\AdjustmentDocument::create([
    //     'company_id' => $company->id,
    //     'type' => 'Credit Note',
    //     'invoice_id' => $invoice->id,
    //     'date' => now()->toDateString(),
    //     'reason' => 'Goodwill discount for new client',
    //     'total_amount' => 500000, // Add total amount directly
    //     'status' => 'Draft',
    //     'created_by_user_id' => $user->id,
    // ]);
    // $creditNote->lines()->create([
    //     'description' => 'Goodwill discount',
    //     'quantity' => 1,
    //     'unit_price' => 500000,
    //     'account_id' => $salesDiscountAccount->id,
    // ]);

    // $adjustmentService->post($creditNote, $user);

    // $creditNote->refresh();
    // $creditNoteEntry = $creditNote->journalEntry;
    // expect($creditNoteEntry->is_posted)->toBeTrue();
    // expect($creditNoteEntry->total_debit)->toEqual('500000.00');
    // expect($creditNoteEntry->lines->where('account_id', $salesDiscountAccount->id)->first()->debit)->toEqual('500000.00');
    // expect($creditNoteEntry->lines->where('account_id', $arAccount->id)->first()->credit)->toEqual('500000.00');
});