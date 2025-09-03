<?php

use App\Actions\Accounting\CreateJournalEntryAction;
use App\Actions\Payments\CreatePaymentAction;
use App\Actions\Purchases\CreateVendorBillAction;
use App\Actions\Sales\CreateInvoiceAction;
use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\DataTransferObjects\Payments\CreatePaymentDocumentLinkDTO;
use App\DataTransferObjects\Payments\CreatePaymentDTO;
use App\DataTransferObjects\Purchases\CreateVendorBillDTO;
use App\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use App\DataTransferObjects\Sales\CreateInvoiceDTO;
use App\DataTransferObjects\Sales\CreateInvoiceLineDTO;
use App\Enums\Adjustments\AdjustmentDocumentStatus;
use App\Enums\Adjustments\AdjustmentDocumentType;
use App\Enums\Partners\PartnerType;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentType;
use App\Enums\Sales\InvoiceStatus;
use App\Models\Account;
use App\Models\Invoice;
use App\Models\Partner;
use App\Services\AdjustmentDocumentService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\VendorBillService;
use Brick\Money\Money;
use Tests\Traits\WithConfiguredCompany;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class, WithConfiguredCompany::class);

test('the entire accounting workflow from setup to credit note', function () {

    // Retrieve essentials from the configured company
    $currency = $this->company->currency;
    $currencyCode = $currency->code;
    $bankAccount = $this->company->defaultBankAccount;
    $arAccount = $this->company->defaultAccountsReceivable;
    $itEquipmentAccount = Account::factory()->for($this->company)->create(['type' => 'current_assets']);
    $apAccount = $this->company->defaultAccountsPayable;
    $equityAccount = Account::factory()->for($this->company)->create(['type' => 'equity']);
    $revenueAccount = Account::factory()->for($this->company)->create(['type' => 'income']);
    $salesDiscountAccount = $this->company->defaultSalesDiscountAccount;
    $bankJournal = $this->company->defaultBankJournal;

    // MODIFIED: Define test-specific amounts using Money objects for precision
    $initialCapitalInvestment = Money::of(15_000_000, $currencyCode);
    $highEndLaptopCost = Money::of(3_000_000, $currencyCode);
    $itInfrastructureServiceCost = Money::of(5_000_000, $currencyCode);
    $goodwillDiscount = Money::of(500_000, $currencyCode);

    // Step 3: Capital Injection
    $createJournalEntryAction = app(CreateJournalEntryAction::class);
    $capitalEntryDto = new CreateJournalEntryDTO(
        company_id: $this->company->id,
        journal_id: $bankJournal->id,
        currency_id: $currency->id,
        entry_date: now()->toDateString(),
        reference: 'Initial Capital Investment',
        description: 'Initial Capital Investment',
        created_by_user_id: $this->user->id,
        is_posted: true,
        lines: [
            new CreateJournalEntryLineDTO(
                account_id: $bankAccount->id,
                debit: $initialCapitalInvestment,
                credit: Money::of(0, $currencyCode),
                description: 'Bank',
                partner_id: null,
                analytic_account_id: null,
            ),
            new CreateJournalEntryLineDTO(
                account_id: $equityAccount->id,
                debit: Money::of(0, $currencyCode),
                credit: $initialCapitalInvestment,
                description: 'Equity',
                partner_id: null,
                analytic_account_id: null,
            ),
        ],
    );
    $capitalEntry = $createJournalEntryAction->execute($capitalEntryDto);

    $this->assertDatabaseHas('journal_entries', ['id' => $capitalEntry->id, 'is_posted' => true]);
    // MODIFIED: Assert against Money objects
    expect($capitalEntry->total_debit->isEqualTo($initialCapitalInvestment))->toBeTrue();
    expect($capitalEntry->total_credit->isEqualTo($initialCapitalInvestment))->toBeTrue();

    // Step 4: Purchasing a Fixed Asset
    $vendor = Partner::factory()->for($this->company)->create(['name' => 'Paykar Tech Supplies', 'type' => PartnerType::Vendor]);
    // Arrange: Prepare the DTOs for the Action.
    $lineDto = new CreateVendorBillLineDTO(
        description: 'High-End Laptop for Business Use',
        quantity: 1,
        unit_price: (string) $highEndLaptopCost->getAmount()->toFloat(), // DTO expects a string for price
        expense_account_id: $itEquipmentAccount->id,
        product_id: null,
        tax_id: null,
        analytic_account_id: null
    );

    $vendorBillDto = new CreateVendorBillDTO(
        company_id: $this->company->id,
        vendor_id: $vendor->id, // <-- Note: DTO uses 'vendor_id'
        currency_id: $currency->id,
        bill_reference: 'KE-LAPTOP-001',
        bill_date: now()->toDateString(),
        accounting_date: now()->toDateString(),
        due_date: now()->addDays(30)->toDateString(),
        lines: [$lineDto],
        created_by_user_id: $this->user->id
    );

    // Act: Create the vendor bill using the Action.
    $vendorBill = (app(CreateVendorBillAction::class))->execute($vendorBillDto);

    // The rest of the test remains the same...
    $vendorBillService = app(VendorBillService::class);

    $vendorBill->refresh();

    $vendorBillService->post($vendorBill, $this->user);

    $vendorBill->refresh();
    $purchaseEntry = $vendorBill->journalEntry;
    expect($purchaseEntry->reference)->toBe($vendorBill->bill_reference);
    expect($purchaseEntry->is_posted)->toBeTrue();
    // MODIFIED: Assert against Money objects and corrected amount
    expect($purchaseEntry->total_debit->isEqualTo($highEndLaptopCost))->toBeTrue();
    expect($purchaseEntry->lines->where('account_id', $itEquipmentAccount->id)->first()->debit->isEqualTo($highEndLaptopCost))->toBeTrue();
    expect($purchaseEntry->lines->where('account_id', $apAccount->id)->first()->credit->isEqualTo($highEndLaptopCost))->toBeTrue();

    // Step 5: Providing a Service & Invoicing
    $customer = Partner::factory()->for($this->company)->create(['name' => 'Hawre Trading Group', 'type' => PartnerType::Customer]);
    $lineDto = new CreateInvoiceLineDTO(
        description: 'On-site IT Infrastructure Setup',
        quantity: 1,
        unit_price: $itInfrastructureServiceCost, // DTO now expects Money object
        income_account_id: $revenueAccount->id,
        product_id: null,
        tax_id: null
    );

    $invoiceDto = new CreateInvoiceDTO(
        company_id: $this->company->id,
        customer_id: $customer->id,
        currency_id: $currency->id,
        invoice_date: now()->toDateString(),
        due_date: now()->addDays(15)->toDateString(),
        lines: [$lineDto],
        fiscal_position_id: null
    );

    // Act: Create the invoice using the Action.
    $invoice = (app(CreateInvoiceAction::class))->execute($invoiceDto);

    // The rest of the test remains the same...
    $invoiceService = app(InvoiceService::class);
    $invoiceService->confirm($invoice, $this->user);

    $invoice->refresh();
    $invoiceEntry = $invoice->journalEntry;
    expect($invoiceEntry->reference)->toBe($invoice->invoice_number);
    expect($invoiceEntry->is_posted)->toBeTrue();
    // MODIFIED: Assert against Money objects and corrected amount
    expect($invoiceEntry->total_debit->isEqualTo($itInfrastructureServiceCost))->toBeTrue();
    expect($invoiceEntry->lines->where('account_id', $arAccount->id)->first()->debit->isEqualTo($itInfrastructureServiceCost))->toBeTrue();
    expect($invoiceEntry->lines->where('account_id', $revenueAccount->id)->first()->credit->isEqualTo($itInfrastructureServiceCost))->toBeTrue();

    // Step 6: Receiving Payment from Customer
    $documentLinkDto = new CreatePaymentDocumentLinkDTO(
        document_type: 'invoice',
        document_id: $invoice->id,
        amount_applied: $itInfrastructureServiceCost
    );

    $paymentDto = new CreatePaymentDTO(
        company_id: $this->company->id,
        journal_id: $bankJournal->id,
        currency_id: $currency->id,
        payment_date: now()->toDateString(),
        payment_purpose: PaymentPurpose::Settlement,
        payment_type: PaymentType::Inbound,
        partner_id: null,
        amount: null,
        counterpart_account_id: null,
        document_links: [$documentLinkDto],
        reference: null
    );

    // Act: Create the payment using the Action.
    $customerPayment = (app(CreatePaymentAction::class))->execute($paymentDto, $this->user);

    // The rest of the test step remains the same...
    $paymentService = app(PaymentService::class);
    $paymentService->confirm($customerPayment, $this->user);

    $customerPayment->refresh();
    $customerPaymentEntry = $customerPayment->journalEntry;
    expect($customerPaymentEntry->is_posted)->toBeTrue();
    // MODIFIED: Assert against Money objects
    expect($customerPaymentEntry->total_debit->isEqualTo($itInfrastructureServiceCost))->toBeTrue();
    expect($customerPaymentEntry->lines->where('account_id', $bankAccount->id)->first()->debit->getAmount()->toFloat())->toEqual($itInfrastructureServiceCost->getAmount()->toFloat());
    expect($customerPaymentEntry->lines->where('account_id', $arAccount->id)->first()->credit->getAmount()->toFloat())->toEqual($itInfrastructureServiceCost->getAmount()->toFloat());
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);

    // Step 7: Paying a Vendor
    $vendorDocumentLinkDto = new CreatePaymentDocumentLinkDTO(
        document_type: 'vendor_bill',
        document_id: $vendorBill->id,
        amount_applied: $highEndLaptopCost
    );

    $vendorPaymentDto = new CreatePaymentDTO(
        company_id: $this->company->id,
        journal_id: $bankJournal->id,
        currency_id: $currency->id,
        payment_date: now()->toDateString(),
        payment_purpose: PaymentPurpose::Settlement,
        payment_type: PaymentType::Outbound,
        partner_id: null,
        amount: null,
        counterpart_account_id: null,
        document_links: [$vendorDocumentLinkDto],
        reference: 'Payment for Laptop'
    );

    // Act: Create the vendor payment using the Action.
    $vendorPayment = (app(CreatePaymentAction::class))->execute($vendorPaymentDto, $this->user);

    $paymentService->confirm($vendorPayment, $this->user);

    $vendorPayment->refresh();
    $vendorPaymentEntry = $vendorPayment->journalEntry;
    expect($vendorPaymentEntry->is_posted)->toBeTrue();
    // MODIFIED: Assert against Money objects
    expect($vendorPaymentEntry->total_debit->isEqualTo($highEndLaptopCost))->toBeTrue();
    expect($vendorPaymentEntry->lines->where('account_id', $apAccount->id)->first()->debit->isEqualTo($highEndLaptopCost))->toBeTrue();
    expect($vendorPaymentEntry->lines->where('account_id', $bankAccount->id)->first()->credit->isEqualTo($highEndLaptopCost))->toBeTrue();
    expect($vendorBill->fresh()->status)->toBe(\App\Enums\Purchases\VendorBillStatus::Paid);

    // Step 8: Handling a Correction (Credit Note)
    $adjustmentService = app(AdjustmentDocumentService::class);

    $creditNote = \App\Models\AdjustmentDocument::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
        'reference_number' => 'CN-001',
        'total_tax' => Money::of(0, $currency->code),
        'type' => AdjustmentDocumentType::CreditNote->value,
        'original_invoice_id' => $invoice->id,
        'date' => now()->toDateString(),
        'reason' => 'Goodwill discount for new client',
        // MODIFIED: Use Money object for total amount
        'total_amount' => $goodwillDiscount,
        'status' => AdjustmentDocumentStatus::Draft,
    ]);

    $adjustmentService->post($creditNote, $this->user);

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
