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
use Brick\Money\Money;
use Livewire\Livewire;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->for($this->company)->create();
    $this->actingAs($this->user);
    
    $this->currency = $this->company->currency;
    
    $this->bankJournal = Journal::factory()
        ->for($this->company)
        ->create(['type' => 'Bank']);
    
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

        Livewire::test(BankReconciliationMatcher::class, ['bankStatementId' => $this->bankStatement->id])
            ->assertCanSeeTableRecords([$unreconciledLine])
            ->assertCannotSeeTableRecords([$reconciledLine]);
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
            ->create(['status' => 'Reconciled']);

        Livewire::test(BankReconciliationMatcher::class, ['bankStatementId' => $this->bankStatement->id])
            ->assertSee($partner->name)
            ->assertDontSee('Reconciled Payment');
    });

    it('can toggle bank line selection', function () {
        $bankLine = BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create(['is_reconciled' => false]);

        $component = Livewire::test(BankReconciliationMatcher::class, ['bankStatementId' => $this->bankStatement->id]);

        // Initially not selected
        $component->assertSet('selectedBankLines', []);

        // Select the line (simulate checkbox click)
        $component->call('table.toggleRecord', $bankLine->id);
        
        // Should be selected now
        $component->assertSet('selectedBankLines', [$bankLine->id]);
    });

    it('can toggle system payment selection', function () {
        $payment = Payment::factory()
            ->for($this->company)
            ->for($this->currency)
            ->for($this->bankJournal)
            ->create(['status' => 'confirmed']);

        $component = Livewire::test(BankReconciliationMatcher::class, ['bankStatementId' => $this->bankStatement->id]);

        // Initially not selected
        $component->assertSet('selectedSystemPayments', []);

        // Select the payment
        $component->call('toggleSystemPayment', $payment->id);
        
        // Should be selected now
        $component->assertSet('selectedSystemPayments', [$payment->id]);

        // Toggle again to deselect
        $component->call('toggleSystemPayment', $payment->id);
        $component->assertSet('selectedSystemPayments', []);
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

        $component = Livewire::test(BankReconciliationMatcher::class, ['bankStatementId' => $this->bankStatement->id])
            ->set('selectedBankLines', [$bankLine->id])
            ->set('selectedSystemPayments', [$payment->id]);

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

        $component = Livewire::test(BankReconciliationMatcher::class, ['bankStatementId' => $this->bankStatement->id])
            ->set('selectedBankLines', [$bankLine->id])
            ->set('selectedSystemPayments', [$payment->id]);

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

        Livewire::test(BankReconciliationMatcher::class, ['bankStatementId' => $this->bankStatement->id])
            ->set('selectedBankLines', [$bankLine->id])
            ->set('selectedSystemPayments', [$payment->id])
            ->call('reconcile')
            ->assertSet('selectedBankLines', [])
            ->assertSet('selectedSystemPayments', []);

        // Verify reconciliation was performed
        expect($bankLine->fresh()->is_reconciled)->toBeTrue();
        expect($payment->fresh()->status)->toBe('Reconciled');
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

        Livewire::test(BankReconciliationMatcher::class, ['bankStatementId' => $this->bankStatement->id])
            ->set('selectedBankLines', [$bankLine->id])
            ->set('selectedSystemPayments', [$payment->id])
            ->call('reconcile');

        // Verify reconciliation was NOT performed
        expect($bankLine->fresh()->is_reconciled)->toBeFalse();
        expect($payment->fresh()->status)->toBe('confirmed');
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

        Livewire::test(BankReconciliationMatcher::class, ['bankStatementId' => $this->bankStatement->id])
            ->callTableAction('writeOff', $bankLine, [
                'write_off_account_id' => $writeOffAccount->id,
                'description' => 'Write-off for small discrepancy',
            ]);

        expect($bankLine->fresh()->is_reconciled)->toBeTrue();
    });
});
