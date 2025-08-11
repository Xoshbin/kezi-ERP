<?php

use App\Filament\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\VendorBill;
use App\Models\Partner;
use App\Models\Journal;
use App\Enums\Accounting\JournalType;
use App\Enums\Purchases\VendorBillStatus;
use App\Enums\Payments\PaymentType;
use App\Enums\Payments\PaymentStatus;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    // Acting as the authenticated user
    $this->actingAs($this->user);
});

it('can render the list page', function () {
    $this->get(PaymentResource::getUrl('index'))->assertSuccessful();
});

it('can render the create page', function () {
    $this->get(PaymentResource::getUrl('create'))->assertSuccessful();
});

it('can create an inbound payment linked to an invoice', function () {
    /** @var \App\Models\Partner $customer */
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    /** @var \App\Models\Invoice $invoice */
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $customer->id,
        'currency_id' => $this->company->currency_id,
        'status' => 'posted',
        'invoice_number' => 'INV-001',
        'total_amount' => Money::of(500, $this->company->currency->code),
    ]);

    /** @var \App\Models\Journal $bankJournal */
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);

    livewire(PaymentResource\Pages\CreatePayment::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'journal_id' => $bankJournal->id,
            'currency_id' => $this->company->currency_id,
            'payment_date' => now()->format('Y-m-d'),
            'reference' => 'Test Payment',
        ])
        ->set('data.document_links', [
            [
                'document_type' => 'invoice',
                'document_id' => $invoice->id,
                'amount_applied' => 500,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('payments', [
        'company_id' => $this->company->id,
        'journal_id' => $bankJournal->id,
        'currency_id' => $this->company->currency_id,
        'reference' => 'Test Payment',
        'payment_type' => PaymentType::Inbound,
        'status' => PaymentStatus::Draft,
    ]);

    $payment = Payment::where('reference', 'Test Payment')->first();
    expect($payment->amount->isEqualTo(Money::of(500, $this->company->currency->code)))->toBeTrue();

    $this->assertDatabaseHas('payment_document_links', [
        'payment_id' => $payment->id,
        'invoice_id' => $invoice->id,
    ]);
});

it('can create an outbound payment linked to a vendor bill', function () {
    /** @var \App\Models\Partner $vendor */
    $vendor = Partner::factory()->vendor()->create([
        'company_id' => $this->company->id,
    ]);

    /** @var \App\Models\VendorBill $vendorBill */
    $vendorBill = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $vendor->id,
        'currency_id' => $this->company->currency_id,
        'status' => VendorBillStatus::Posted,
        'bill_reference' => 'BILL-001',
        'total_amount' => Money::of(300, $this->company->currency->code),
    ]);

    /** @var \App\Models\Journal $bankJournal */
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);

    livewire(PaymentResource\Pages\CreatePayment::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'journal_id' => $bankJournal->id,
            'currency_id' => $this->company->currency_id,
            'payment_date' => now()->format('Y-m-d'),
            'reference' => 'Vendor Payment',
        ])
        ->set('data.document_links', [
            [
                'document_type' => 'vendor_bill',
                'document_id' => $vendorBill->id,
                'amount_applied' => 300,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('payments', [
        'company_id' => $this->company->id,
        'journal_id' => $bankJournal->id,
        'currency_id' => $this->company->currency_id,
        'reference' => 'Vendor Payment',
        'payment_type' => PaymentType::Outbound,
        'status' => PaymentStatus::Draft,
    ]);

    $payment = Payment::where('reference', 'Vendor Payment')->first();
    expect($payment->amount->isEqualTo(Money::of(300, $this->company->currency->code)))->toBeTrue();

    $this->assertDatabaseHas('payment_document_links', [
        'payment_id' => $payment->id,
        'vendor_bill_id' => $vendorBill->id,
    ]);
});

it('can validate input on create', function () {
    livewire(PaymentResource\Pages\CreatePayment::class)
        ->fillForm([
            'company_id' => null,
            'journal_id' => null,
            'currency_id' => null,
            'payment_date' => null,
            'document_links' => [],
        ])
        ->call('create')
        ->assertHasFormErrors([
            'company_id' => 'required',
            'journal_id' => 'required',
            'currency_id' => 'required',
            'payment_date' => 'required',
            'document_links' => 'min',
        ]);
});

it('can render the edit page', function () {
    $payment = Payment::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->get(PaymentResource::getUrl('edit', ['record' => $payment]))
        ->assertSuccessful();
});

it('can edit a draft payment', function () {
    /** @var \App\Models\Partner $customer */
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    /** @var \App\Models\Invoice $invoice */
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $customer->id,
        'currency_id' => $this->company->currency_id,
        'status' => 'posted',
        'invoice_number' => 'INV-002',
        'total_amount' => Money::of(100, $this->company->currency->code),
    ]);

    $payment = Payment::factory()->create([
        'company_id' => $this->company->id,
        'status' => PaymentStatus::Draft,
        'reference' => 'Old Reference',
        'amount' => Money::of(100, $this->company->currency->code),
        'currency_id' => $this->company->currency_id,
    ]);

    // Create a payment document link
    $payment->paymentDocumentLinks()->create([
        'invoice_id' => $invoice->id,
        'amount_applied' => Money::of(100, $this->company->currency->code),
    ]);

    livewire(PaymentResource\Pages\EditPayment::class, [
        'record' => $payment->getRouteKey(),
    ])
        ->fillForm([
            'reference' => 'New Reference',
        ])
        ->set('data.document_links', [
            [
                'document_type' => 'invoice',
                'document_id' => $invoice->id,
                'amount_applied' => 150,
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $payment->refresh();
    expect($payment->reference)->toBe('New Reference');
    expect($payment->amount->isEqualTo(Money::of(150, $this->company->currency->code)))->toBeTrue();
});

it('cannot edit a confirmed payment', function () {
    /** @var \App\Models\Partner $customer */
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    /** @var \App\Models\Invoice $invoice */
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $customer->id,
        'currency_id' => $this->company->currency_id,
        'status' => 'posted',
        'invoice_number' => 'INV-007',
        'total_amount' => Money::of(100, $this->company->currency->code),
    ]);

    $payment = Payment::factory()->create([
        'company_id' => $this->company->id,
        'status' => PaymentStatus::Confirmed,
        'reference' => 'Confirmed Payment',
        'amount' => Money::of(100, $this->company->currency->code),
        'currency_id' => $this->company->currency_id,
    ]);

    // Create a payment document link
    $payment->paymentDocumentLinks()->create([
        'invoice_id' => $invoice->id,
        'amount_applied' => Money::of(100, $this->company->currency->code),
    ]);

    // Attempting to edit a confirmed payment should throw an exception
    expect(function () use ($payment) {
        livewire(PaymentResource\Pages\EditPayment::class, [
            'record' => $payment->getRouteKey(),
        ])
            ->fillForm([
                'reference' => 'Should Not Change',
            ])
            ->call('save');
    })->toThrow(\App\Exceptions\UpdateNotAllowedException::class, 'Only draft payments can be updated.');

    // Payment should remain unchanged
    $payment->refresh();
    expect($payment->reference)->toBe('Confirmed Payment');
    expect($payment->amount->isEqualTo(Money::of(100, $this->company->currency->code)))->toBeTrue();
});

it('can confirm a draft payment', function () {
    /** @var \App\Models\Partner $customer */
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    /** @var \App\Models\Invoice $invoice */
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $customer->id,
        'currency_id' => $this->company->currency_id,
        'status' => 'posted',
        'invoice_number' => 'INV-003',
        'total_amount' => Money::of(100, $this->company->currency->code),
    ]);

    $payment = Payment::factory()->create([
        'company_id' => $this->company->id,
        'status' => PaymentStatus::Draft,
        'amount' => Money::of(100, $this->company->currency->code),
        'currency_id' => $this->company->currency_id,
        'paid_to_from_partner_id' => $customer->id,
        'payment_type' => PaymentType::Inbound,
    ]);

    // Create a payment document link
    $payment->paymentDocumentLinks()->create([
        'invoice_id' => $invoice->id,
        'amount_applied' => Money::of(100, $this->company->currency->code),
    ]);

    livewire(PaymentResource\Pages\EditPayment::class, [
        'record' => $payment->getRouteKey(),
    ])
        ->callAction('confirm');

    expect($payment->fresh()->status)->toBe(PaymentStatus::Confirmed);
});

it('shows cancel action for confirmed payments', function () {
    /** @var \App\Models\Partner $customer */
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    // Create a journal entry for the payment
    $journalEntry = \App\Models\JournalEntry::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
        'is_posted' => true,
    ]);

    $payment = Payment::factory()->create([
        'company_id' => $this->company->id,
        'status' => PaymentStatus::Confirmed,
        'amount' => Money::of(100, $this->company->currency->code),
        'currency_id' => $this->company->currency_id,
        'paid_to_from_partner_id' => $customer->id,
        'payment_type' => PaymentType::Inbound,
        'journal_entry_id' => $journalEntry->id,
    ]);

    livewire(PaymentResource\Pages\EditPayment::class, [
        'record' => $payment->getRouteKey(),
    ])
        ->assertActionVisible('cancel');
});

it('can delete a draft payment', function () {
    $payment = Payment::factory()->create([
        'company_id' => $this->company->id,
        'status' => PaymentStatus::Draft,
        'amount' => Money::of(100, $this->company->currency->code),
        'currency_id' => $this->company->currency_id,
    ]);

    livewire(PaymentResource\Pages\EditPayment::class, [
        'record' => $payment->getRouteKey(),
    ])
        ->callAction('delete');

    $this->assertModelMissing($payment);
});

it('cannot delete a confirmed payment', function () {
    $payment = Payment::factory()->create([
        'company_id' => $this->company->id,
        'status' => PaymentStatus::Confirmed,
    ]);

    livewire(PaymentResource\Pages\EditPayment::class, [
        'record' => $payment->getRouteKey(),
    ])
        ->assertActionHidden('delete');
});

it('calculates total amount from multiple document links', function () {
    /** @var \App\Models\Partner $customer */
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    /** @var \App\Models\Invoice $invoice1 */
    $invoice1 = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $customer->id,
        'currency_id' => $this->company->currency_id,
        'status' => 'posted',
        'invoice_number' => 'INV-005',
        'total_amount' => Money::of(300, $this->company->currency->code),
    ]);

    /** @var \App\Models\Invoice $invoice2 */
    $invoice2 = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $customer->id,
        'currency_id' => $this->company->currency_id,
        'status' => 'posted',
        'invoice_number' => 'INV-006',
        'total_amount' => Money::of(200, $this->company->currency->code),
    ]);

    /** @var \App\Models\Journal $bankJournal */
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);

    livewire(PaymentResource\Pages\CreatePayment::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'journal_id' => $bankJournal->id,
            'currency_id' => $this->company->currency_id,
            'payment_date' => now()->format('Y-m-d'),
            'reference' => 'Multi Invoice Payment',
        ])
        ->set('data.document_links', [
            [
                'document_type' => 'invoice',
                'document_id' => $invoice1->id,
                'amount_applied' => 300,
            ],
            [
                'document_type' => 'invoice',
                'document_id' => $invoice2->id,
                'amount_applied' => 150,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $payment = Payment::where('reference', 'Multi Invoice Payment')->first();
    expect($payment->amount->isEqualTo(Money::of(450, $this->company->currency->code)))->toBeTrue();
});

it('can display journal entries relation manager', function () {
    /** @var \App\Models\Partner $customer */
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    /** @var \App\Models\Journal $bankJournal */
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);

    /** @var \App\Models\Payment $payment */
    $payment = Payment::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $bankJournal->id,
        'currency_id' => $this->company->currency_id,
        'paid_to_from_partner_id' => $customer->id,
        'payment_type' => PaymentType::Inbound,
        'status' => PaymentStatus::Confirmed,
        'amount' => Money::of(1000, $this->company->currency->code),
    ]);

    // Create a journal entry linked to this payment
    $journalEntry = \App\Models\JournalEntry::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $bankJournal->id,
        'currency_id' => $this->company->currency_id,
        'source_type' => Payment::class,
        'source_id' => $payment->id,
        'reference' => 'PAY/' . $payment->id,
        'description' => 'Payment journal entry',
        'total_debit' => Money::of(1000, $this->company->currency->code),
        'total_credit' => Money::of(1000, $this->company->currency->code),
        'is_posted' => true,
        'created_by_user_id' => $this->user->id,
    ]);

    // Test that the journal entries relation manager can be rendered
    livewire(\App\Filament\Resources\PaymentResource\RelationManagers\JournalEntriesRelationManager::class, [
        'ownerRecord' => $payment,
        'pageClass' => \App\Filament\Resources\PaymentResource\Pages\EditPayment::class,
    ])
        ->assertCanSeeTableRecords([$journalEntry])
        ->assertCanRenderTableColumn('reference')
        ->assertCanRenderTableColumn('entry_date')
        ->assertCanRenderTableColumn('total_debit')
        ->assertCanRenderTableColumn('total_credit')
        ->assertCanRenderTableColumn('source_type');
});

it('can display bank statement lines relation manager for reconciled payment', function () {
    /** @var \App\Models\Partner $customer */
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    /** @var \App\Models\Journal $bankJournal */
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);

    /** @var \App\Models\Payment $payment */
    $payment = Payment::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $bankJournal->id,
        'currency_id' => $this->company->currency_id,
        'paid_to_from_partner_id' => $customer->id,
        'payment_type' => PaymentType::Inbound,
        'status' => PaymentStatus::Reconciled,
        'amount' => Money::of(1000, $this->company->currency->code),
    ]);

    // Create a bank statement and line linked to this payment
    $bankStatement = \App\Models\BankStatement::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
        'reference' => 'STMT-001',
    ]);

    $bankStatementLine = \App\Models\BankStatementLine::factory()->create([
        'bank_statement_id' => $bankStatement->id,
        'payment_id' => $payment->id,
        'description' => 'Payment from customer',
        'amount' => Money::of(1000, $this->company->currency->code),
        'is_reconciled' => true,
    ]);

    // Test that the bank statement lines relation manager can be rendered
    livewire(\App\Filament\Resources\PaymentResource\RelationManagers\BankStatementLinesRelationManager::class, [
        'ownerRecord' => $payment,
        'pageClass' => \App\Filament\Resources\PaymentResource\Pages\EditPayment::class,
    ])
        ->assertCanSeeTableRecords([$bankStatementLine])
        ->assertCanRenderTableColumn('date')
        ->assertCanRenderTableColumn('description')
        ->assertCanRenderTableColumn('amount')
        ->assertCanRenderTableColumn('is_reconciled');
});
