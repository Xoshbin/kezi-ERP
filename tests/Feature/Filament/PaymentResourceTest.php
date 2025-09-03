<?php

use App\Enums\Accounting\JournalType;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Payments\PaymentType;
use App\Filament\Clusters\Accounting\Resources\Payments\PaymentResource;
use App\Models\Journal;
use App\Models\Partner;
use App\Models\Payment;
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

it('can create a standalone inbound payment', function () {
    /** @var \App\Models\Partner $customer */
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    /** @var \App\Models\Journal $bankJournal */
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);

    /** @var \App\Models\Account $incomeAccount */
    $incomeAccount = \App\Models\Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => \App\Enums\Accounting\AccountType::Income->value,
    ]);

    livewire(\App\Filament\Clusters\Accounting\Resources\Payments\Pages\CreatePayment::class)
        ->fillForm([
            'journal_id' => $bankJournal->id,
            'currency_id' => $this->company->currency_id,
            'payment_date' => now()->format('Y-m-d'),
            'payment_type' => PaymentType::Inbound->value,
            'payment_purpose' => PaymentPurpose::Loan->value,
            'partner_id' => $customer->id,
            'amount' => 500,
            'counterpart_account_id' => $incomeAccount->id,
            'reference' => 'Standalone Payment',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('payments', [
        'company_id' => $this->company->id,
        'journal_id' => $bankJournal->id,
        'currency_id' => $this->company->currency_id,
        'reference' => 'Standalone Payment',
        'payment_type' => PaymentType::Inbound,
        'payment_purpose' => PaymentPurpose::Loan,
        'status' => PaymentStatus::Draft,
    ]);

    $payment = Payment::where('reference', 'Standalone Payment')->first();
    expect($payment->amount->isEqualTo(Money::of(500, $this->company->currency->code)))->toBeTrue();
    expect($payment->paid_to_from_partner_id)->toBe($customer->id);
    expect($payment->counterpart_account_id)->toBe($incomeAccount->id);
});

it('can create a standalone outbound payment', function () {
    /** @var \App\Models\Partner $vendor */
    $vendor = Partner::factory()->vendor()->create([
        'company_id' => $this->company->id,
    ]);

    /** @var \App\Models\Journal $bankJournal */
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);

    /** @var \App\Models\Account $expenseAccount */
    $expenseAccount = \App\Models\Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => \App\Enums\Accounting\AccountType::Expense->value,
    ]);

    livewire(\App\Filament\Clusters\Accounting\Resources\Payments\Pages\CreatePayment::class)
        ->fillForm([
            'journal_id' => $bankJournal->id,
            'currency_id' => $this->company->currency_id,
            'payment_date' => now()->format('Y-m-d'),
            'payment_type' => PaymentType::Outbound->value,
            'payment_purpose' => PaymentPurpose::Loan->value,
            'partner_id' => $vendor->id,
            'amount' => 300,
            'counterpart_account_id' => $expenseAccount->id,
            'reference' => 'Standalone Vendor Payment',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('payments', [
        'company_id' => $this->company->id,
        'journal_id' => $bankJournal->id,
        'currency_id' => $this->company->currency_id,
        'reference' => 'Standalone Vendor Payment',
        'payment_type' => PaymentType::Outbound,
        'payment_purpose' => PaymentPurpose::Loan,
        'status' => PaymentStatus::Draft,
    ]);

    $payment = Payment::where('reference', 'Standalone Vendor Payment')->first();
    expect($payment->amount->isEqualTo(Money::of(300, $this->company->currency->code)))->toBeTrue();
    expect($payment->paid_to_from_partner_id)->toBe($vendor->id);
    expect($payment->counterpart_account_id)->toBe($expenseAccount->id);
});

it('can validate input on create', function () {
    livewire(\App\Filament\Clusters\Accounting\Resources\Payments\Pages\CreatePayment::class)
        ->fillForm([
            'journal_id' => null,
            'currency_id' => null,
            'payment_date' => null,
            'payment_type' => null,
            'payment_purpose' => null,
            'partner_id' => null,
            'amount' => null,
            'counterpart_account_id' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'journal_id' => 'required',
            'currency_id' => 'required',
            'payment_date' => 'required',
            'payment_type' => 'required',
            'payment_purpose' => 'required',
            'partner_id' => 'required',
            'amount' => 'required',
            'counterpart_account_id' => 'required',
        ]);
});

it('can render the edit page', function () {
    $payment = Payment::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->get(PaymentResource::getUrl('edit', ['record' => $payment]))
        ->assertSuccessful();
});

it('can edit a draft standalone payment', function () {
    /** @var \App\Models\Partner $customer */
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    /** @var \App\Models\Account $incomeAccount */
    $incomeAccount = \App\Models\Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => \App\Enums\Accounting\AccountType::Income->value,
    ]);

    $payment = Payment::factory()->create([
        'company_id' => $this->company->id,
        'status' => PaymentStatus::Draft,
        'payment_type' => PaymentType::Inbound,
        'payment_purpose' => PaymentPurpose::Loan,
        'paid_to_from_partner_id' => $customer->id,
        'counterpart_account_id' => $incomeAccount->id,
        'reference' => 'Old Reference',
        'amount' => Money::of(100, $this->company->currency->code),
        'currency_id' => $this->company->currency_id,
    ]);

    livewire(\App\Filament\Clusters\Accounting\Resources\Payments\Pages\EditPayment::class, [
        'record' => $payment->getRouteKey(),
    ])
        ->fillForm([
            'journal_id' => $payment->journal_id,
            'currency_id' => $payment->currency_id,
            'payment_date' => $payment->payment_date->format('Y-m-d'),
            'payment_type' => $payment->payment_type->value,
            'payment_purpose' => $payment->payment_purpose->value,
            'partner_id' => $payment->paid_to_from_partner_id,
            'amount' => 150,
            'counterpart_account_id' => $payment->counterpart_account_id,
            'reference' => 'New Reference',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $payment->refresh();
    expect($payment->reference)->toBe('New Reference');
    expect($payment->amount->isEqualTo(Money::of(150, $this->company->currency->code)))->toBeTrue();
});

it('cannot edit a confirmed standalone payment', function () {
    /** @var \App\Models\Partner $customer */
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    /** @var \App\Models\Account $incomeAccount */
    $incomeAccount = \App\Models\Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => \App\Enums\Accounting\AccountType::Income->value,
    ]);

    $payment = Payment::factory()->create([
        'company_id' => $this->company->id,
        'status' => PaymentStatus::Confirmed,
        'payment_type' => PaymentType::Inbound,
        'payment_purpose' => PaymentPurpose::Loan,
        'paid_to_from_partner_id' => $customer->id,
        'counterpart_account_id' => $incomeAccount->id,
        'reference' => 'Confirmed Payment',
        'amount' => Money::of(100, $this->company->currency->code),
        'currency_id' => $this->company->currency_id,
    ]);

    // Attempting to edit a confirmed payment should throw an exception
    expect(function () use ($payment) {
        livewire(\App\Filament\Clusters\Accounting\Resources\Payments\Pages\EditPayment::class, [
            'record' => $payment->getRouteKey(),
        ])
            ->fillForm([
                'journal_id' => $payment->journal_id,
                'currency_id' => $payment->currency_id,
                'payment_date' => $payment->payment_date->format('Y-m-d'),
                'payment_type' => $payment->payment_type->value,
                'payment_purpose' => $payment->payment_purpose->value,
                'partner_id' => $payment->paid_to_from_partner_id,
                'amount' => $payment->amount->getAmount()->toFloat(),
                'counterpart_account_id' => $payment->counterpart_account_id,
                'reference' => 'Should Not Change',
            ])
            ->call('save');
    })->toThrow(\App\Exceptions\UpdateNotAllowedException::class, 'Only draft payments can be updated.');

    // Payment should remain unchanged
    $payment->refresh();
    expect($payment->reference)->toBe('Confirmed Payment');
    expect($payment->amount->isEqualTo(Money::of(100, $this->company->currency->code)))->toBeTrue();
});

it('can confirm a draft standalone payment', function () {
    /** @var \App\Models\Partner $customer */
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    /** @var \App\Models\Account $incomeAccount */
    $incomeAccount = \App\Models\Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => \App\Enums\Accounting\AccountType::Income->value,
    ]);

    /** @var \App\Models\Account $bankAccount */
    $bankAccount = \App\Models\Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => \App\Enums\Accounting\AccountType::BankAndCash->value,
    ]);

    /** @var \App\Models\Journal $bankJournal */
    $bankJournal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'type' => JournalType::Bank,
        'default_debit_account_id' => $bankAccount->id,
        'default_credit_account_id' => $bankAccount->id,
        'currency_id' => $this->company->currency_id,
    ]);

    $payment = Payment::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $bankJournal->id,
        'status' => PaymentStatus::Draft,
        'payment_purpose' => PaymentPurpose::Loan,
        'counterpart_account_id' => $incomeAccount->id,
        'amount' => Money::of(100, $this->company->currency->code),
        'currency_id' => $this->company->currency_id,
        'paid_to_from_partner_id' => $customer->id,
        'payment_type' => PaymentType::Inbound,
    ]);

    $component = livewire(\App\Filament\Clusters\Accounting\Resources\Payments\Pages\EditPayment::class, [
        'record' => $payment->getRouteKey(),
    ]);

    // First ensure the form is properly filled
    $component->fillForm([
        'journal_id' => $payment->journal_id,
        'currency_id' => $payment->currency_id,
        'payment_date' => $payment->payment_date->format('Y-m-d'),
        'payment_type' => $payment->payment_type->value,
        'payment_purpose' => $payment->payment_purpose->value,
        'partner_id' => $payment->paid_to_from_partner_id,
        'amount' => $payment->amount->getAmount()->toFloat(),
        'counterpart_account_id' => $payment->counterpart_account_id,
        'reference' => $payment->reference,
    ]);

    // Then call the confirm action
    $component->callAction('confirm');

    expect($payment->fresh()->status)->toBe(PaymentStatus::Confirmed);
});

// it('shows cancel action for confirmed payments', function () {
//     /** @var \App\Models\Partner $customer */
//     $customer = Partner::factory()->customer()->create([
//         'company_id' => $this->company->id,
//     ]);

//     // Create a journal entry for the payment
//     $journalEntry = \App\Models\JournalEntry::factory()->create([
//         'company_id' => $this->company->id,
//         'currency_id' => $this->company->currency_id,
//         'is_posted' => true,
//     ]);

//     $payment = Payment::factory()->create([
//         'company_id' => $this->company->id,
//         'status' => PaymentStatus::Confirmed,
//         'amount' => Money::of(100, $this->company->currency->code),
//         'currency_id' => $this->company->currency_id,
//         'paid_to_from_partner_id' => $customer->id,
//         'payment_type' => PaymentType::Inbound,
//         'journal_entry_id' => $journalEntry->id,
//     ]);

//     livewire(\App\Filament\Resources\Payments\Pages\EditPayment::class, [
//         'record' => $payment->getRouteKey(),
//     ])
//         ->assertActionVisible('cancel');
// });

it('can delete a draft payment', function () {
    $payment = Payment::factory()->create([
        'company_id' => $this->company->id,
        'status' => PaymentStatus::Draft,
        'amount' => Money::of(100, $this->company->currency->code),
        'currency_id' => $this->company->currency_id,
    ]);

    livewire(\App\Filament\Clusters\Accounting\Resources\Payments\Pages\EditPayment::class, [
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

    livewire(\App\Filament\Clusters\Accounting\Resources\Payments\Pages\EditPayment::class, [
        'record' => $payment->getRouteKey(),
    ])
        ->assertActionHidden('delete');
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
        'reference' => 'PAY/'.$payment->id,
        'description' => 'Payment journal entry',
        'total_debit' => Money::of(1000, $this->company->currency->code),
        'total_credit' => Money::of(1000, $this->company->currency->code),
        'is_posted' => true,
        'created_by_user_id' => $this->user->id,
    ]);

    // Test that the journal entries relation manager can be rendered
    livewire(\App\Filament\Clusters\Accounting\Resources\Payments\RelationManagers\JournalEntriesRelationManager::class, [
        'ownerRecord' => $payment,
        'pageClass' => \App\Filament\Clusters\Accounting\Resources\Payments\Pages\EditPayment::class,
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
    livewire(\App\Filament\Clusters\Accounting\Resources\Payments\RelationManagers\BankStatementLinesRelationManager::class, [
        'ownerRecord' => $payment,
        'pageClass' => \App\Filament\Clusters\Accounting\Resources\Payments\Pages\EditPayment::class,
    ])
        ->assertCanSeeTableRecords([$bankStatementLine])
        ->assertCanRenderTableColumn('date')
        ->assertCanRenderTableColumn('description')
        ->assertCanRenderTableColumn('amount')
        ->assertCanRenderTableColumn('is_reconciled');
});
