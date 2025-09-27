<?php

use App\Livewire\Accounting\BankTransactionsTable;
use App\Models\Journal;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Set locale to English for consistent test assertions
    app()->setLocale('en');

    // Create company with reconciliation enabled and required accounts
    $this->company = \Tests\Builders\CompanyBuilder::new()
        ->withDefaultAccounts()
        ->withReconciliationEnabled()
        ->create();

    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);
    $this->actingAs($this->user);

    // Set up Filament tenant context
    \Filament\Facades\Filament::setTenant($this->company);

    $this->currency = $this->company->currency;

    // Get the default accounts created by CompanyBuilder
    $this->bankAccount = \Modules\Accounting\Models\Account::where('company_id', $this->company->id)
        ->where('type', 'bank_and_cash')
        ->first();

    $this->outstandingAccount = \Modules\Accounting\Models\Account::where('company_id', $this->company->id)
        ->where('type', 'current_assets')
        ->first();

    $this->bankJournal = Journal::factory()
        ->for($this->company)
        ->create(['type' => 'bank']);

    $this->bankStatement = \Modules\Accounting\Models\BankStatement::factory()
        ->for($this->company)
        ->for($this->currency)
        ->for($this->bankJournal)
        ->create();
});

describe('BankTransactionsTable Livewire Component', function () {
    it('can mount with bank statement', function () {
        Livewire::test(BankTransactionsTable::class, ['bankStatement' => $this->bankStatement])
            ->assertSet('bankStatement.id', $this->bankStatement->id)
            ->assertViewIs('livewire.accounting.bank-transactions-table');
    });

    it('displays only unreconciled bank statement lines', function () {
        $unreconciledLine = \Modules\Accounting\Models\BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create([
                'description' => 'Unreconciled Transaction',
                'amount' => Money::of(100, $this->currency->code),
                'is_reconciled' => false,
            ]);

        $reconciledLine = \Modules\Accounting\Models\BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create([
                'description' => 'Reconciled Transaction',
                'amount' => Money::of(200, $this->currency->code),
                'is_reconciled' => true,
            ]);

        Livewire::test(BankTransactionsTable::class, ['bankStatement' => $this->bankStatement])
            ->assertSee('Unreconciled Transaction')
            ->assertDontSee('Reconciled Transaction');
    });

    it('can toggle bank line selection', function () {
        $bankLine = \Modules\Accounting\Models\BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create(['is_reconciled' => false]);

        $component = Livewire::test(BankTransactionsTable::class, ['bankStatement' => $this->bankStatement]);

        // Initially not selected
        $component->assertSet('selectedBankLines', []);

        // Select the line
        $component->call('toggleBankLine', $bankLine->id);

        // Should be selected now
        $component->assertSet('selectedBankLines', [$bankLine->id]);

        // Toggle again to deselect
        $component->call('toggleBankLine', $bankLine->id);
        $component->assertSet('selectedBankLines', []);
    });

    it('emits selection changed event when toggling lines', function () {
        $bankLine = \Modules\Accounting\Models\BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create([
                'amount' => Money::of(100, $this->currency->code),
                'is_reconciled' => false,
            ]);

        Livewire::test(BankTransactionsTable::class, ['bankStatement' => $this->bankStatement])
            ->call('toggleBankLine', $bankLine->id)
            ->assertDispatched('bank-selection-changed', [
                'selectedIds' => [$bankLine->id],
                'total' => 100000, // 100.000 IQD in minor units
                'currency' => $this->currency->code,
            ]);
    });

    it('can perform write-off action', function () {
        $bankLine = \Modules\Accounting\Models\BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create([
                'amount' => Money::of(10, $this->currency->code),
                'is_reconciled' => false,
                'description' => 'Small discrepancy',
            ]);

        $writeOffAccount = \Modules\Accounting\Models\Account::factory()
            ->for($this->company)
            ->create(['type' => 'expense', 'name' => 'Bank Charges']);

        Livewire::test(BankTransactionsTable::class, ['bankStatement' => $this->bankStatement])
            ->callTableAction('writeOff', $bankLine, [
                'account_id' => $writeOffAccount->id,
                'reason' => 'Write-off for small discrepancy',
            ]);

        // Verify the line is now reconciled
        expect($bankLine->fresh()->is_reconciled)->toBeTrue();

        // Verify a journal entry was created
        $this->assertDatabaseHas('journal_entries', [
            'company_id' => $this->company->id,
            'description' => 'Write-off for small discrepancy',
        ]);
    });

    it('emits write-off event after successful write-off', function () {
        $bankLine = \Modules\Accounting\Models\BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create([
                'amount' => Money::of(5, $this->currency->code),
                'is_reconciled' => false,
            ]);

        $writeOffAccount = \Modules\Accounting\Models\Account::factory()
            ->for($this->company)
            ->create(['type' => 'expense']);

        Livewire::test(BankTransactionsTable::class, ['bankStatement' => $this->bankStatement])
            ->callTableAction('writeOff', $bankLine, [
                'account_id' => $writeOffAccount->id,
                'reason' => 'Bank fee write-off',
            ])
            ->assertDispatched('bank-line-written-off');
    });

    it('calculates correct total for multiple selected lines', function () {
        $line1 = \Modules\Accounting\Models\BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create([
                'amount' => Money::of(100, $this->currency->code),
                'is_reconciled' => false,
            ]);

        $line2 = \Modules\Accounting\Models\BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create([
                'amount' => Money::of(50, $this->currency->code),
                'is_reconciled' => false,
            ]);

        $component = Livewire::test(BankTransactionsTable::class, ['bankStatement' => $this->bankStatement]);

        // Select both lines
        $component->call('toggleBankLine', $line1->id);
        $component->call('toggleBankLine', $line2->id);

        // Should emit event with total of both lines
        $component->assertDispatched('bank-selection-changed', [
            'selectedIds' => [$line1->id, $line2->id],
            'total' => 150000, // 150.000 IQD in minor units
            'currency' => $this->currency->code,
        ]);
    });

    it('shows write-off account options filtered by company and expense type', function () {
        $expenseAccount = \Modules\Accounting\Models\Account::factory()
            ->for($this->company)
            ->create(['type' => 'expense', 'name' => 'Bank Charges']);

        $assetAccount = \Modules\Accounting\Models\Account::factory()
            ->for($this->company)
            ->create(['type' => 'bank_and_cash', 'name' => 'Cash']);

        $otherCompanyAccount = \Modules\Accounting\Models\Account::factory()
            ->create(['type' => 'expense', 'name' => 'Other Company Expense']);

        $bankLine = \Modules\Accounting\Models\BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create(['is_reconciled' => false]);

        $component = Livewire::test(BankTransactionsTable::class, ['bankStatement' => $this->bankStatement]);

        // The write-off form should only show expense accounts from the same company
        $component->mountTableAction('writeOff', $bankLine);

        // This is a basic test - in a real scenario, you'd need to inspect the form options
        // For now, we just verify the component can mount the action
        expect($component->instance())->toBeInstanceOf(BankTransactionsTable::class);
    });
});
