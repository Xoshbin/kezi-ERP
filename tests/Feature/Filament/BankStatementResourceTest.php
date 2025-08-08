<?php

use App\Filament\Resources\BankStatementResource;
use App\Models\BankStatement;
use App\Models\Partner;
use App\Models\Journal;
use App\Enums\Accounting\JournalType;
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
    $this->get(BankStatementResource::getUrl('index'))->assertSuccessful();
});

it('can render the create page', function () {
    $this->get(BankStatementResource::getUrl('create'))->assertSuccessful();
});

it('can create a bank statement', function () {
    /** @var \App\Models\Partner $partner */
    $partner = Partner::factory()->create([
        'company_id' => $this->company->id,
    ]);

    /** @var \App\Models\Journal $bankJournal */
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);

    livewire(BankStatementResource\Pages\CreateBankStatement::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'journal_id' => $bankJournal->id,
            'reference' => 'Test Statement Ref',
            'date' => now()->format('Y-m-d'),
            'starting_balance' => 1000.00,
            'ending_balance' => 1500.00,
        ])
        ->set('data.bankStatementLines', [
            [
                'date' => now()->format('Y-m-d'),
                'description' => 'Test Transaction',
                'amount' => 500.00,
                'partner_id' => $partner->id,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('bank_statements', [
        'company_id' => $this->company->id,
        'journal_id' => $bankJournal->id,
        'reference' => 'Test Statement Ref',
    ]);

    $this->assertDatabaseHas('bank_statement_lines', [
        'description' => 'Test Transaction',
        'partner_id' => $partner->id,
        'amount' => 500000, // Stored in minor units (500.00 * 1000 for IQD)
    ]);

    $bankStatement = BankStatement::first();
    expect($bankStatement->starting_balance->isEqualTo(Money::of('1000.00', $this->company->currency->code)))->toBeTrue();
    expect($bankStatement->ending_balance->isEqualTo(Money::of('1500.00', $this->company->currency->code)))->toBeTrue();
});

it('can validate input on create', function () {
    livewire(BankStatementResource\Pages\CreateBankStatement::class)
        ->fillForm([
            'company_id' => null,
            'currency_id' => null,
            'journal_id' => null,
            'reference' => null,
            'date' => null,
            'starting_balance' => null,
            'ending_balance' => null,
            'bankStatementLines' => [],
        ])
        ->call('create')
        ->assertHasFormErrors([
            'company_id' => 'required',
            'currency_id' => 'required',
            'journal_id' => 'required',
            'reference' => 'required',
            'date' => 'required',
            'starting_balance' => 'required',
            'ending_balance' => 'required',
            'bankStatementLines' => 'min',
        ]);
});

it('can render the edit page', function () {
    $bankStatement = BankStatement::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->get(BankStatementResource::getUrl('edit', ['record' => $bankStatement]))
        ->assertSuccessful();
});

it('can edit a bank statement', function () {
    $bankStatement = BankStatement::factory()->create([
        'company_id' => $this->company->id,
        'reference' => 'Old Ref',
    ]);

    // Create a line for the bank statement
    $bankStatement->bankStatementLines()->create([
        'company_id' => $this->company->id,
        'date' => now(),
        'description' => 'Original Line',
        'amount' => Money::of(100, $this->company->currency->code),
    ]);

    /** @var \App\Models\Partner $newPartner */
    $newPartner = Partner::factory()->create([
        'company_id' => $this->company->id,
    ]);

    livewire(BankStatementResource\Pages\EditBankStatement::class, [
        'record' => $bankStatement->getRouteKey(),
    ])
        ->fillForm([
            'reference' => 'New Ref',
            'starting_balance' => 2000.00,
            'ending_balance' => 2500.00,
        ])
        ->set('data.bankStatementLines', [
            [
                'date' => now()->format('Y-m-d'),
                'description' => 'Updated Line',
                'amount' => 500.00,
                'partner_id' => $newPartner->id,
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('bank_statements', [
        'id' => $bankStatement->id,
        'reference' => 'New Ref',
    ]);

    $bankStatement->refresh();
    expect($bankStatement->starting_balance->isEqualTo(Money::of('2000.00', $this->company->currency->code)))->toBeTrue();
    expect($bankStatement->ending_balance->isEqualTo(Money::of('2500.00', $this->company->currency->code)))->toBeTrue();
});

it('can render the view page', function () {
    $bankStatement = BankStatement::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->get(BankStatementResource::getUrl('view', ['record' => $bankStatement]))
        ->assertSuccessful();
});

it('can render the reconcile page', function () {
    $bankStatement = BankStatement::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->get(BankStatementResource::getUrl('reconcile', ['record' => $bankStatement]))
        ->assertSuccessful();
});

it('preserves the reconcile button in the table', function () {
    $bankStatement = BankStatement::factory()->create([
        'company_id' => $this->company->id,
    ]);

    livewire(BankStatementResource\Pages\ListBankStatements::class)
        ->assertCanSeeTableRecords([$bankStatement])
        ->assertTableActionExists('reconcile');
});

it('can handle multiple lines in create', function () {
    /** @var \App\Models\Partner $partner1 */
    $partner1 = Partner::factory()->create(['company_id' => $this->company->id]);

    /** @var \App\Models\Partner $partner2 */
    $partner2 = Partner::factory()->create(['company_id' => $this->company->id]);

    /** @var \App\Models\Journal $bankJournal */
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);

    livewire(BankStatementResource\Pages\CreateBankStatement::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'journal_id' => $bankJournal->id,
            'reference' => 'Multi-Line Statement',
            'date' => now()->format('Y-m-d'),
            'starting_balance' => 1000.00,
            'ending_balance' => 1400.00,
        ])
        ->set('data.bankStatementLines', [
            [
                'date' => now()->format('Y-m-d'),
                'description' => 'Income Transaction',
                'amount' => 500.00,
                'partner_id' => $partner1->id,
            ],
            [
                'date' => now()->format('Y-m-d'),
                'description' => 'Expense Transaction',
                'amount' => -100.00,
                'partner_id' => $partner2->id,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseCount('bank_statement_lines', 2);

    $bankStatement = BankStatement::first();
    expect($bankStatement->bankStatementLines)->toHaveCount(2);
});

it('handles money objects correctly in forms', function () {
    /** @var \App\Models\Journal $bankJournal */
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);

    $bankStatement = BankStatement::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
        'journal_id' => $bankJournal->id,
        'starting_balance' => Money::of(1000, $this->company->currency->code),
        'ending_balance' => Money::of(1500, $this->company->currency->code),
    ]);

    // Create a line with Money object
    $bankStatement->bankStatementLines()->create([
        'company_id' => $this->company->id,
        'date' => now(),
        'description' => 'Money Object Line',
        'amount' => Money::of(500, $this->company->currency->code),
    ]);

    // Test that the edit page loads correctly with Money objects
    livewire(BankStatementResource\Pages\EditBankStatement::class, [
        'record' => $bankStatement->getRouteKey(),
    ])
        ->assertFormSet([
            'reference' => $bankStatement->reference,
        ])
        ->assertHasNoFormErrors();
});

it('can reconcile bank statement lines with payments', function () {
    /** @var \App\Models\Journal $bankJournal */
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);

    // Set up required company accounts for reconciliation
    $bankAccount = \App\Models\Account::factory()->for($this->company)->create(['type' => 'Bank']);
    $outstandingAccount = \App\Models\Account::factory()->for($this->company)->create(['type' => 'Receivable']);

    $this->company->update([
        'default_bank_account_id' => $bankAccount->id,
        'default_outstanding_receipts_account_id' => $outstandingAccount->id,
    ]);

    $bankStatement = BankStatement::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
        'journal_id' => $bankJournal->id,
    ]);

    // Create a bank statement line
    $statementLine = $bankStatement->bankStatementLines()->create([
        'company_id' => $this->company->id,
        'date' => now(),
        'description' => 'Customer Payment',
        'amount' => Money::of(1000, $this->company->currency->code),
        'is_reconciled' => false,
    ]);

    // Create a matching payment
    $payment = \App\Models\Payment::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $bankJournal->id,
        'currency_id' => $this->company->currency_id,
        'amount' => Money::of(1000, $this->company->currency->code),
        'payment_type' => 'inbound',
        'status' => \App\Models\Payment::STATUS_CONFIRMED,
    ]);

    // Test the BankReconciliation component reactivity
    $reconciliationComponent = livewire(\App\Livewire\BankReconciliation::class, [
        'record' => $bankStatement,
    ]);

    $reconciliationComponent->set('selectedLines', [$statementLine->id])
                           ->set('selectedPayments', [$payment->id]);

    // Verify the component shows it can reconcile
    expect($reconciliationComponent->get('canReconcile'))->toBeTrue();
    expect($reconciliationComponent->get('difference')->isZero())->toBeTrue();

    // Note: Actual reconciliation action would need to be implemented in the component
    // For now, we're testing the reactive behavior
});

it('can create write-off entries for unmatched bank statement lines', function () {
    /** @var \App\Models\Journal $bankJournal */
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);

    $bankStatement = BankStatement::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
        'journal_id' => $bankJournal->id,
    ]);

    // Create an unmatched bank statement line
    $statementLine = $bankStatement->bankStatementLines()->create([
        'company_id' => $this->company->id,
        'date' => now(),
        'description' => 'Bank Fee',
        'amount' => Money::of(-25, $this->company->currency->code),
        'is_reconciled' => false,
    ]);

    // Create a write-off account
    $writeOffAccount = \App\Models\Account::factory()->for($this->company)->create([
        'type' => 'Expense',
        'name' => 'Bank Charges',
    ]);

    // Test the write-off action
    livewire(BankStatementResource\Pages\ReconcileBankStatement::class, [
        'record' => $bankStatement->getRouteKey(),
    ])
        ->callAction('writeOff', [
            'line_id' => $statementLine->id,
            'write_off_account_id' => $writeOffAccount->id,
            'description' => 'Bank service fee write-off',
        ])
        ->assertNotified('Write-off created successfully');

    // Verify write-off was created
    $statementLine->refresh();
    expect($statementLine->is_reconciled)->toBeTrue();

    // Verify journal entry was created
    $this->assertDatabaseHas('journal_entries', [
        'company_id' => $this->company->id,
        'description' => 'Bank service fee write-off',
    ]);
});

it('prevents reconciliation when totals do not match', function () {
    /** @var \App\Models\Journal $bankJournal */
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);

    $bankStatement = BankStatement::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
        'journal_id' => $bankJournal->id,
    ]);

    // Create a bank statement line
    $statementLine = $bankStatement->bankStatementLines()->create([
        'company_id' => $this->company->id,
        'date' => now(),
        'description' => 'Customer Payment',
        'amount' => Money::of(1000, $this->company->currency->code),
        'is_reconciled' => false,
    ]);

    // Create a payment with different amount
    $payment = \App\Models\Payment::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $bankJournal->id,
        'currency_id' => $this->company->currency_id,
        'amount' => Money::of(500, $this->company->currency->code), // Different amount
        'payment_type' => 'inbound',
        'status' => \App\Models\Payment::STATUS_CONFIRMED,
    ]);

    // Test that reconciliation button is disabled when totals don't match
    // Test the BankReconciliation component with mismatched amounts
    $reconciliationComponent = livewire(\App\Livewire\BankReconciliation::class, [
        'record' => $bankStatement,
    ]);

    $reconciliationComponent->set('selectedLines', [$statementLine->id])
                           ->set('selectedPayments', [$payment->id]);

    // Verify the component shows it cannot reconcile (amounts don't match)
    expect($reconciliationComponent->get('canReconcile'))->toBeFalse();

    // Verify nothing was reconciled
    $statementLine->refresh();
    $payment->refresh();

    expect($statementLine->is_reconciled)->toBeFalse();
    expect($payment->status)->toBe(\App\Models\Payment::STATUS_CONFIRMED);
});

it('can clear selections in reconciliation interface', function () {
    /** @var \App\Models\Journal $bankJournal */
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);

    $bankStatement = BankStatement::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
        'journal_id' => $bankJournal->id,
    ]);

    // Create a bank statement line
    $statementLine = $bankStatement->bankStatementLines()->create([
        'company_id' => $this->company->id,
        'date' => now(),
        'description' => 'Test Transaction',
        'amount' => Money::of(1000, $this->company->currency->code),
        'is_reconciled' => false,
    ]);

    // Test the clear selections functionality in BankReconciliation component
    $reconciliationComponent = livewire(\App\Livewire\BankReconciliation::class, [
        'record' => $bankStatement,
    ]);

    $reconciliationComponent->set('selectedLines', [$statementLine->id])
                           ->assertSet('selectedLines', [$statementLine->id])
                           ->call('clearSelection')
                           ->assertSet('selectedLines', [])
                           ->assertSet('selectedPayments', []);
});

it('has reactive reconciliation summary', function () {
    /** @var \App\Models\Journal $bankJournal */
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);

    $bankStatement = BankStatement::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
        'journal_id' => $bankJournal->id,
    ]);

    // Create a bank statement line
    $statementLine = $bankStatement->bankStatementLines()->create([
        'company_id' => $this->company->id,
        'date' => now(),
        'description' => 'Test Transaction',
        'amount' => Money::of(1000, $this->company->currency->code),
        'is_reconciled' => false,
    ]);

    // Create a matching payment
    $payment = \App\Models\Payment::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $bankJournal->id,
        'currency_id' => $this->company->currency_id,
        'amount' => Money::of(1000, $this->company->currency->code),
        'payment_type' => 'inbound',
        'status' => \App\Models\Payment::STATUS_CONFIRMED,
    ]);

    // Test the BankReconciliation Livewire component directly
    $reconciliationComponent = livewire(\App\Livewire\BankReconciliation::class, [
        'record' => $bankStatement,
    ]);

    // Initially, no selections should mean canReconcile is false
    expect($reconciliationComponent->get('canReconcile'))->toBeFalse();

    // Select matching items
    $reconciliationComponent->set('selectedLines', [$statementLine->id])
                           ->set('selectedPayments', [$payment->id]);

    // Now canReconcile should be true since totals match
    expect($reconciliationComponent->get('canReconcile'))->toBeTrue();
    expect($reconciliationComponent->get('difference')->isZero())->toBeTrue();
});

it('can toggle bank lines and payments in reconciliation interface', function () {
    /** @var \App\Models\Journal $bankJournal */
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);

    $bankStatement = BankStatement::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
        'journal_id' => $bankJournal->id,
    ]);

    $statementLine = $bankStatement->bankStatementLines()->create([
        'company_id' => $this->company->id,
        'date' => now(),
        'description' => 'Test Transaction',
        'amount' => Money::of(1000, $this->company->currency->code),
        'is_reconciled' => false,
    ]);

    $payment = \App\Models\Payment::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $bankJournal->id,
        'currency_id' => $this->company->currency_id,
        'amount' => Money::of(1000, $this->company->currency->code),
        'payment_type' => 'inbound',
        'status' => \App\Models\Payment::STATUS_CONFIRMED,
    ]);

    $reconciliationComponent = livewire(\App\Livewire\BankReconciliation::class, [
        'record' => $bankStatement,
    ]);

    // Test toggling bank line
    $reconciliationComponent->call('toggleBankLine', $statementLine->id)
                           ->assertSet('selectedLines', [$statementLine->id]);

    // Test toggling payment
    $reconciliationComponent->call('togglePayment', $payment->id)
                           ->assertSet('selectedPayments', [$payment->id]);

    // Test toggling off
    $reconciliationComponent->call('toggleBankLine', $statementLine->id)
                           ->assertSet('selectedLines', []);

    $reconciliationComponent->call('togglePayment', $payment->id)
                           ->assertSet('selectedPayments', []);
});

it('can perform reconciliation through the livewire component', function () {
    /** @var \App\Models\Journal $bankJournal */
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);

    $bankStatement = BankStatement::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
        'journal_id' => $bankJournal->id,
    ]);

    $statementLine = $bankStatement->bankStatementLines()->create([
        'company_id' => $this->company->id,
        'date' => now(),
        'description' => 'Test Transaction',
        'amount' => Money::of(1000, $this->company->currency->code),
        'is_reconciled' => false,
    ]);

    $payment = \App\Models\Payment::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $bankJournal->id,
        'currency_id' => $this->company->currency_id,
        'amount' => Money::of(1000, $this->company->currency->code),
        'payment_type' => 'inbound',
        'status' => \App\Models\Payment::STATUS_CONFIRMED,
    ]);

    $reconciliationComponent = livewire(\App\Livewire\BankReconciliation::class, [
        'record' => $bankStatement,
    ]);

    // Select matching items
    $reconciliationComponent->set('selectedLines', [$statementLine->id])
                           ->set('selectedPayments', [$payment->id]);

    // Verify can reconcile
    expect($reconciliationComponent->get('canReconcile'))->toBeTrue();

    // Perform reconciliation
    $reconciliationComponent->call('reconcile');

    // Verify selections were cleared
    expect($reconciliationComponent->get('selectedLines'))->toBeEmpty();
    expect($reconciliationComponent->get('selectedPayments'))->toBeEmpty();
});

it('calculates totals correctly with different payment types', function () {
    /** @var \App\Models\Journal $bankJournal */
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);

    $bankStatement = BankStatement::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
        'journal_id' => $bankJournal->id,
    ]);

    // Create a positive bank statement line (deposit)
    $depositLine = $bankStatement->bankStatementLines()->create([
        'company_id' => $this->company->id,
        'date' => now(),
        'description' => 'Customer Payment',
        'amount' => Money::of(1000, $this->company->currency->code),
        'is_reconciled' => false,
    ]);

    // Create a negative bank statement line (withdrawal)
    $withdrawalLine = $bankStatement->bankStatementLines()->create([
        'company_id' => $this->company->id,
        'date' => now(),
        'description' => 'Vendor Payment',
        'amount' => Money::of(-500, $this->company->currency->code),
        'is_reconciled' => false,
    ]);

    // Create an inbound payment (money coming in)
    $inboundPayment = \App\Models\Payment::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $bankJournal->id,
        'currency_id' => $this->company->currency_id,
        'amount' => Money::of(1000, $this->company->currency->code),
        'payment_type' => 'inbound',
        'status' => \App\Models\Payment::STATUS_CONFIRMED,
    ]);

    // Create an outbound payment (money going out)
    $outboundPayment = \App\Models\Payment::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $bankJournal->id,
        'currency_id' => $this->company->currency_id,
        'amount' => Money::of(500, $this->company->currency->code),
        'payment_type' => 'outbound',
        'status' => \App\Models\Payment::STATUS_CONFIRMED,
    ]);

    $reconciliationComponent = livewire(\App\Livewire\BankReconciliation::class, [
        'record' => $bankStatement,
    ]);

    // Test 1: Select deposit line and inbound payment (both positive)
    $reconciliationComponent->set('selectedLines', [$depositLine->id])
                           ->set('selectedPayments', [$inboundPayment->id]);

    expect($reconciliationComponent->get('selectedBankTotal')->getAmount()->toInt())->toBe(1000); // 1000.00 in major units
    expect($reconciliationComponent->get('selectedPaymentTotal')->getAmount()->toInt())->toBe(1000); // 1000.00 in major units
    expect($reconciliationComponent->get('difference')->isZero())->toBeTrue();
    expect($reconciliationComponent->get('canReconcile'))->toBeTrue();

    // Test 2: Select withdrawal line and outbound payment (both negative)
    $reconciliationComponent->set('selectedLines', [$withdrawalLine->id])
                           ->set('selectedPayments', [$outboundPayment->id]);

    expect($reconciliationComponent->get('selectedBankTotal')->getAmount()->toInt())->toBe(-500); // -500.00 in major units
    expect($reconciliationComponent->get('selectedPaymentTotal')->getAmount()->toInt())->toBe(-500); // -500.00 (outbound) in major units
    expect($reconciliationComponent->get('difference')->isZero())->toBeTrue();
    expect($reconciliationComponent->get('canReconcile'))->toBeTrue();

    // Test 3: Select both lines and both payments (should net to 500)
    $reconciliationComponent->set('selectedLines', [$depositLine->id, $withdrawalLine->id])
                           ->set('selectedPayments', [$inboundPayment->id, $outboundPayment->id]);

    expect($reconciliationComponent->get('selectedBankTotal')->getAmount()->toInt())->toBe(500); // 1000 - 500 = 500 in major units
    expect($reconciliationComponent->get('selectedPaymentTotal')->getAmount()->toInt())->toBe(500); // 1000 - 500 = 500 in major units
    expect($reconciliationComponent->get('difference')->isZero())->toBeTrue();
    expect($reconciliationComponent->get('canReconcile'))->toBeTrue();
});

it('can write off bank statement lines', function () {
    /** @var \App\Models\Journal $bankJournal */
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);

    $bankStatement = BankStatement::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
        'journal_id' => $bankJournal->id,
    ]);

    // Create an unreconciled bank statement line
    $line = $bankStatement->bankStatementLines()->create([
        'company_id' => $this->company->id,
        'date' => now(),
        'description' => 'Unmatched Transaction',
        'amount' => Money::of(100, $this->company->currency->code),
        'is_reconciled' => false,
    ]);

    $reconciliationComponent = livewire(\App\Livewire\BankReconciliation::class, [
        'record' => $bankStatement,
    ]);

    // Write off the bank line
    $reconciliationComponent->call('writeOffBankLine', $line->id);

    // Verify the line is now reconciled
    $line->refresh();
    expect($line->is_reconciled)->toBeTrue();
});

it('can write off payments', function () {
    /** @var \App\Models\Journal $bankJournal */
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);

    $bankStatement = BankStatement::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
        'journal_id' => $bankJournal->id,
    ]);

    // Create an unmatched payment
    $payment = \App\Models\Payment::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $bankJournal->id,
        'currency_id' => $this->company->currency_id,
        'amount' => Money::of(100, $this->company->currency->code),
        'payment_type' => 'inbound',
        'status' => \App\Models\Payment::STATUS_CONFIRMED,
    ]);

    $reconciliationComponent = livewire(\App\Livewire\BankReconciliation::class, [
        'record' => $bankStatement,
    ]);

    // Write off the payment
    $reconciliationComponent->call('writeOffPayment', $payment->id);

    // Verify the payment is now reconciled
    $payment->refresh();
    expect($payment->status)->toBe(\App\Models\Payment::STATUS_RECONCILED);
});
