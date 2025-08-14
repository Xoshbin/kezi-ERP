<?php

use App\Models\User;
use App\Models\Company;
use App\Models\Currency;
use App\Models\BankStatement;
use App\Models\BankStatementLine;
use App\Models\Payment;
use App\Models\Account;
use App\Models\Journal;
use App\Models\Partner;
use App\Livewire\Accounting\BankReconciliationMatcher;
use App\Livewire\Accounting\BankTransactionsTable;
use App\Livewire\Accounting\SystemPaymentsTable;
use Brick\Money\Money;
use App\Enums\Payments\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);
    $this->actingAs($this->user);

    // Set Filament tenant context
    \Filament\Facades\Filament::setTenant($this->company);

    $this->currency = $this->company->currency;

    // Create required accounts for reconciliation
    $this->bankAccount = Account::factory()
        ->for($this->company)
        ->create(['type' => 'bank_and_cash', 'name' => 'Bank Account']);

    $this->outstandingAccount = Account::factory()
        ->for($this->company)
        ->create(['type' => 'current_assets', 'name' => 'Outstanding Receipts']);

    // Update company with default accounts
    $this->company->update([
        'default_bank_account_id' => $this->bankAccount->id,
        'default_outstanding_receipts_account_id' => $this->outstandingAccount->id,
    ]);

    $this->bankJournal = Journal::factory()
        ->for($this->company)
        ->create(['type' => 'bank']);

    $this->bankStatement = BankStatement::factory()
        ->for($this->company)
        ->for($this->currency)
        ->for($this->bankJournal)
        ->create();
});

describe('BankReconciliationMatcher Livewire Component', function () {
    it('can mount with bank statement ID', function () {
        Livewire::test(BankReconciliationMatcher::class, ['bankStatementId' => $this->bankStatement->id])
            ->assertSet('bankStatementId', $this->bankStatement->id)
            ->assertViewIs('livewire.accounting.bank-reconciliation-matcher');
    });

    it('displays unreconciled bank statement lines', function () {
        $unreconciledLine = BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create([
                'description' => 'Test Transaction',
                'amount' => Money::of(100, $this->currency->code),
                'is_reconciled' => false,
            ]);

        $reconciledLine = BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create(['is_reconciled' => true]);

        // Test the BankTransactionsTable component directly
        Livewire::test(BankTransactionsTable::class, ['bankStatement' => $this->bankStatement])
            ->assertSee($unreconciledLine->description)
            ->assertDontSee($reconciledLine->description);
    });

    it('displays unreconciled system payments', function () {
        $partner = Partner::factory()->for($this->company)->create();

        $unreconciledPayment = Payment::factory()
            ->for($this->company)
            ->for($this->currency)
            ->for($this->bankJournal)
            ->for($partner, 'partner')
            ->create([
                'status' => 'confirmed',
                'amount' => Money::of(100, $this->currency->code),
            ]);

        $reconciledPayment = Payment::factory()
            ->for($this->company)
            ->for($this->currency)
            ->for($this->bankJournal)
            ->create(['status' => PaymentStatus::Reconciled]);

        // Test the SystemPaymentsTable component directly
        Livewire::test(SystemPaymentsTable::class, ['bankStatement' => $this->bankStatement])
            ->assertSee($partner->name)
            ->assertDontSee('Reconciled Payment');
    });

    it('can toggle bank line selection', function () {
        $bankLine = BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create(['is_reconciled' => false]);

        // Test the BankTransactionsTable component directly
        $component = Livewire::test(BankTransactionsTable::class, ['bankStatement' => $this->bankStatement]);

        // Initially not selected
        $component->assertSet('selectedBankLines', []);

        // Select the line (simulate checkbox click)
        $component->call('toggleBankLine', $bankLine->id);

        // Should be selected now
        $component->assertSet('selectedBankLines', [$bankLine->id]);
    });

    it('can toggle system payment selection', function () {
        $payment = Payment::factory()
            ->for($this->company)
            ->for($this->currency)
            ->for($this->bankJournal)
            ->create(['status' => 'confirmed']);

        // Test the SystemPaymentsTable component directly
        $component = Livewire::test(SystemPaymentsTable::class, ['bankStatement' => $this->bankStatement]);

        // Initially not selected
        $component->assertSet('selectedPayments', []);

        // Select the payment
        $component->call('togglePayment', $payment->id);

        // Should be selected now
        $component->assertSet('selectedPayments', [$payment->id]);

        // Toggle again to deselect
        $component->call('togglePayment', $payment->id);
        $component->assertSet('selectedPayments', []);
    });

    it('calculates summary correctly for balanced selection', function () {
        $bankLine = BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create([
                'amount' => Money::of(100, $this->currency->code),
                'is_reconciled' => false,
            ]);

        $payment = Payment::factory()
            ->for($this->company)
            ->for($this->currency)
            ->for($this->bankJournal)
            ->create([
                'amount' => Money::of(100, $this->currency->code),
                'payment_type' => 'inbound',
                'status' => 'confirmed',
            ]);

        $component = Livewire::test(BankReconciliationMatcher::class, ['bankStatementId' => $this->bankStatement->id]);

        // Simulate events from child components
        $component->call('updateBankSelection', [
            'selectedIds' => [$bankLine->id],
            'total' => 100000, // 100.000 IQD in minor units (3 decimal places)
            'currency' => $this->currency->code,
        ]);

        $component->call('updatePaymentSelection', [
            'selectedIds' => [$payment->id],
            'total' => 100000, // 100.000 IQD in minor units (3 decimal places)
            'currency' => $this->currency->code,
        ]);

        $summary = $component->get('summary');

        expect($summary['isBalanced'])->toBeTrue();
        expect($summary['bankTotal']->isEqualTo(Money::of(100, $this->currency->code)))->toBeTrue();
        expect($summary['systemTotal']->isEqualTo(Money::of(100, $this->currency->code)))->toBeTrue();
        expect($summary['difference']->isZero())->toBeTrue();
    });

    it('calculates summary correctly for unbalanced selection', function () {
        $bankLine = BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create([
                'amount' => Money::of(100, $this->currency->code),
                'is_reconciled' => false,
            ]);

        $payment = Payment::factory()
            ->for($this->company)
            ->for($this->currency)
            ->for($this->bankJournal)
            ->create([
                'amount' => Money::of(50, $this->currency->code),
                'payment_type' => 'inbound',
                'status' => 'confirmed',
            ]);

        $component = Livewire::test(BankReconciliationMatcher::class, ['bankStatementId' => $this->bankStatement->id]);

        // Simulate events from child components
        $component->call('updateBankSelection', [
            'selectedIds' => [$bankLine->id],
            'total' => 100000, // 100.000 IQD in minor units (3 decimal places)
            'currency' => $this->currency->code,
        ]);

        $component->call('updatePaymentSelection', [
            'selectedIds' => [$payment->id],
            'total' => 50000, // 50.000 IQD in minor units (3 decimal places)
            'currency' => $this->currency->code,
        ]);

        $summary = $component->get('summary');

        expect($summary['isBalanced'])->toBeFalse();
        expect($summary['difference']->isEqualTo(Money::of(50, $this->currency->code)))->toBeTrue();
    });

    it('can perform reconciliation when balanced', function () {
        $bankLine = BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create([
                'amount' => Money::of(100, $this->currency->code),
                'is_reconciled' => false,
            ]);

        $payment = Payment::factory()
            ->for($this->company)
            ->for($this->currency)
            ->for($this->bankJournal)
            ->create([
                'amount' => Money::of(100, $this->currency->code),
                'payment_type' => 'inbound',
                'status' => 'confirmed',
            ]);

        $component = Livewire::test(BankReconciliationMatcher::class, ['bankStatementId' => $this->bankStatement->id]);

        // Simulate balanced selections via events
        $component->call('updateBankSelection', [
            'selectedIds' => [$bankLine->id],
            'total' => 100000, // 100.000 IQD in minor units (3 decimal places)
            'currency' => $this->currency->code,
        ]);

        $component->call('updatePaymentSelection', [
            'selectedIds' => [$payment->id],
            'total' => 100000, // 100.000 IQD in minor units (3 decimal places)
            'currency' => $this->currency->code,
        ]);

        $component->call('reconcile')
            ->assertSet('selectedBankLines', [])
            ->assertSet('selectedPayments', []);

        // Verify reconciliation was performed
        expect($bankLine->fresh()->is_reconciled)->toBeTrue();
        expect($payment->fresh()->status)->toBe(PaymentStatus::Reconciled);
    });

    it('prevents reconciliation when not balanced', function () {
        $bankLine = BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create([
                'amount' => Money::of(100, $this->currency->code),
                'is_reconciled' => false,
            ]);

        $payment = Payment::factory()
            ->for($this->company)
            ->for($this->currency)
            ->for($this->bankJournal)
            ->create([
                'amount' => Money::of(50, $this->currency->code),
                'payment_type' => 'inbound',
                'status' => 'confirmed',
            ]);

        $component = Livewire::test(BankReconciliationMatcher::class, ['bankStatementId' => $this->bankStatement->id]);

        // Simulate unbalanced selections via events
        $component->call('updateBankSelection', [
            'selectedIds' => [$bankLine->id],
            'total' => 100000, // 100.000 IQD in minor units (3 decimal places)
            'currency' => $this->currency->code,
        ]);

        $component->call('updatePaymentSelection', [
            'selectedIds' => [$payment->id],
            'total' => 50000, // 50.000 IQD in minor units (3 decimal places)
            'currency' => $this->currency->code,
        ]);

        $component->call('reconcile');

        // Verify reconciliation was NOT performed
        expect($bankLine->fresh()->is_reconciled)->toBeFalse();
        expect($payment->fresh()->status)->toBe(PaymentStatus::Confirmed);
    });

    it('can create write-offs through table action', function () {
        $bankLine = BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create([
                'amount' => Money::of(10, $this->currency->code),
                'is_reconciled' => false,
                'description' => 'Small discrepancy',
            ]);

        $writeOffAccount = Account::factory()
            ->for($this->company)
            ->create(['type' => 'expense']);

        // Test the write-off action on the BankTransactionsTable component
        Livewire::test(BankTransactionsTable::class, ['bankStatement' => $this->bankStatement])
            ->callTableAction('writeOff', $bankLine, [
                'account_id' => $writeOffAccount->id,
                'reason' => 'Write-off for small discrepancy',
            ]);

        expect($bankLine->fresh()->is_reconciled)->toBeTrue();
    });
});
