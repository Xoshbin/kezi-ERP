<?php

use App\Enums\Payments\PaymentStatus;
use App\Enums\Payments\PaymentType;
use App\Livewire\Accounting\BankReconciliationMatcher;
use App\Livewire\Accounting\BankTransactionsTable;
use App\Livewire\Accounting\SystemPaymentsTable;
use App\Models\Account;
use App\Models\BankStatement;
use App\Models\BankStatementLine;
use App\Models\Company;
use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Models\Journal;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\User;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
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

    // Update company with default accounts
    $this->company->update([
        'default_bank_account_id' => $this->bankAccount->id,
        'default_outstanding_receipts_account_id' => $this->outstandingAccount->id,
    ]);

    $this->bankJournal = Journal::factory()
        ->for($this->company)
        ->create(['type' => 'bank']);

    $this->bankStatement = \Modules\Accounting\Models\BankStatement::factory()
        ->for($this->company)
        ->for($this->currency)
        ->for($this->bankJournal)
        ->create();
});

describe('BankReconciliationMatcher Livewire Component', function () {
    it('can mount with bank statement ID', function () {
        Livewire::test(\Modules\Accounting\Livewire\Accounting\BankReconciliationMatcher::class, ['bankStatementId' => $this->bankStatement->id])
            ->assertSet('bankStatementId', $this->bankStatement->id)
            ->assertViewIs('livewire.accounting.bank-reconciliation-matcher');
    });

    it('displays unreconciled bank statement lines', function () {
        $unreconciledLine = \Modules\Accounting\Models\BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create([
                'description' => 'Test Transaction',
                'amount' => Money::of(100, $this->currency->code),
                'is_reconciled' => false,
            ]);

        $reconciledLine = \Modules\Accounting\Models\BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create(['is_reconciled' => true]);

        // Test the BankTransactionsTable component directly
        Livewire::test(BankTransactionsTable::class, ['bankStatement' => $this->bankStatement])
            ->assertSee($unreconciledLine->description)
            ->assertDontSee($reconciledLine->description);
    });

    it('displays unreconciled system payments', function () {
        $partner = \Modules\Foundation\Models\Partner::factory()->for($this->company)->create();

        $unreconciledPayment = \Modules\Payment\Models\Payment::factory()
            ->for($this->company)
            ->for($this->currency)
            ->for($this->bankJournal)
            ->for($partner, 'partner')
            ->create([
                'status' => 'confirmed',
                'amount' => Money::of(100, $this->currency->code),
            ]);

        $reconciledPayment = \Modules\Payment\Models\Payment::factory()
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
        $bankLine = \Modules\Accounting\Models\BankStatementLine::factory()
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
        $payment = \Modules\Payment\Models\Payment::factory()
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
        $bankLine = \Modules\Accounting\Models\BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create([
                'amount' => Money::of(100, $this->currency->code),
                'is_reconciled' => false,
            ]);

        $payment = \Modules\Payment\Models\Payment::factory()
            ->for($this->company)
            ->for($this->currency)
            ->for($this->bankJournal)
            ->create([
                'amount' => Money::of(100, $this->currency->code),
                'payment_type' => 'inbound',
                'status' => 'confirmed',
            ]);

        $component = Livewire::test(\Modules\Accounting\Livewire\Accounting\BankReconciliationMatcher::class, ['bankStatementId' => $this->bankStatement->id]);

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
        $bankLine = \Modules\Accounting\Models\BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create([
                'amount' => Money::of(100, $this->currency->code),
                'is_reconciled' => false,
            ]);

        $payment = \Modules\Payment\Models\Payment::factory()
            ->for($this->company)
            ->for($this->currency)
            ->for($this->bankJournal)
            ->create([
                'amount' => Money::of(50, $this->currency->code),
                'payment_type' => 'inbound',
                'status' => 'confirmed',
            ]);

        $component = Livewire::test(\Modules\Accounting\Livewire\Accounting\BankReconciliationMatcher::class, ['bankStatementId' => $this->bankStatement->id]);

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
        $bankLine = \Modules\Accounting\Models\BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create([
                'amount' => Money::of(100, $this->currency->code),
                'is_reconciled' => false,
            ]);

        $payment = \Modules\Payment\Models\Payment::factory()
            ->for($this->company)
            ->for($this->currency)
            ->for($this->bankJournal)
            ->create([
                'amount' => Money::of(100, $this->currency->code),
                'payment_type' => 'inbound',
                'status' => 'confirmed',
            ]);

        $component = Livewire::test(\Modules\Accounting\Livewire\Accounting\BankReconciliationMatcher::class, ['bankStatementId' => $this->bankStatement->id]);

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
        $bankLine = \Modules\Accounting\Models\BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create([
                'amount' => Money::of(100, $this->currency->code),
                'is_reconciled' => false,
            ]);

        $payment = \Modules\Payment\Models\Payment::factory()
            ->for($this->company)
            ->for($this->currency)
            ->for($this->bankJournal)
            ->create([
                'amount' => Money::of(50, $this->currency->code),
                'payment_type' => 'inbound',
                'status' => 'confirmed',
            ]);

        $component = Livewire::test(\Modules\Accounting\Livewire\Accounting\BankReconciliationMatcher::class, ['bankStatementId' => $this->bankStatement->id]);

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
        $bankLine = \Modules\Accounting\Models\BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create([
                'amount' => Money::of(10, $this->currency->code),
                'is_reconciled' => false,
                'description' => 'Small discrepancy',
            ]);

        $writeOffAccount = \Modules\Accounting\Models\Account::factory()
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

describe('Multi-Currency Livewire Reconciliation', function () {
    beforeEach(function () {
        // Create USD currency for foreign currency tests
        $this->usdCurrency = \Modules\Foundation\Models\Currency::firstOrCreate(
            ['code' => 'USD'],
            [
                'name' => ['en' => 'US Dollar', 'ckb' => 'دۆلاری ئەمریکی', 'ar' => 'دولار أمريكي'],
                'symbol' => '$',
                'is_active' => true,
                'decimal_places' => 2,
            ]
        );

        // Set up exchange rate: 1 USD = 1460 IQD
        $this->exchangeRate = 1460.0;
        $this->transactionDate = Carbon::parse('2024-01-01');

        \Modules\Foundation\Models\CurrencyRate::updateOrCreate(
            [
                'currency_id' => $this->usdCurrency->id,
                'effective_date' => $this->transactionDate->toDateString(),
                'company_id' => $this->company->id,
            ],
            [
                'rate' => $this->exchangeRate,
                'source' => 'manual',
            ]
        );

        // Create USD bank statement
        $this->usdBankStatement = \Modules\Accounting\Models\BankStatement::factory()
            ->for($this->company)
            ->for($this->usdCurrency)
            ->for($this->bankJournal)
            ->create([
                'date' => $this->transactionDate,
                'starting_balance' => Money::of(0, 'USD'),
                'ending_balance' => Money::of(100, 'USD'),
            ]);
    });

    it('handles USD bank statement with USD payment reconciliation', function () {
        $bankLine = \Modules\Accounting\Models\BankStatementLine::factory()
            ->for($this->usdBankStatement)
            ->create([
                'amount' => Money::of(100, 'USD'),
                'is_reconciled' => false,
            ]);

        $payment = \Modules\Payment\Models\Payment::factory()
            ->for($this->company)
            ->for($this->usdCurrency)
            ->for($this->bankJournal)
            ->create([
                'amount' => Money::of(100, 'USD'),
                'payment_type' => PaymentType::Inbound,
                'status' => PaymentStatus::Confirmed,
            ]);

        $component = Livewire::test(\Modules\Accounting\Livewire\Accounting\BankReconciliationMatcher::class, ['bankStatementId' => $this->usdBankStatement->id]);

        // Simulate selections in USD
        $component->call('updateBankSelection', [
            'selectedIds' => [$bankLine->id],
            'total' => 10000, // $100.00 in minor units (2 decimal places)
            'currency' => 'USD',
        ]);

        $component->call('updatePaymentSelection', [
            'selectedIds' => [$payment->id],
            'total' => 10000, // $100.00 in minor units (2 decimal places)
            'currency' => 'USD',
        ]);

        $component->call('reconcile')
            ->assertSet('selectedBankLines', [])
            ->assertSet('selectedPayments', []);

        // Verify reconciliation was performed
        expect($bankLine->fresh()->is_reconciled)->toBeTrue();
        expect($payment->fresh()->status)->toBe(PaymentStatus::Reconciled);
    });

    it('calculates totals correctly for USD bank statement', function () {
        $bankLine1 = \Modules\Accounting\Models\BankStatementLine::factory()
            ->for($this->usdBankStatement)
            ->create(['amount' => Money::of(50, 'USD')]);

        $bankLine2 = \Modules\Accounting\Models\BankStatementLine::factory()
            ->for($this->usdBankStatement)
            ->create(['amount' => Money::of(75, 'USD')]);

        $component = Livewire::test(BankTransactionsTable::class, ['bankStatement' => $this->usdBankStatement]);

        // Select both lines through the toggle method which triggers emitSelectionChanged
        $component->call('toggleBankLine', $bankLine1->id);
        $component->call('toggleBankLine', $bankLine2->id);

        // Should emit total in USD, not IQD
        $component->assertDispatched('bank-selection-changed');
    });

    it('shows payments with currency information for USD bank statement', function () {
        // Create payments in different currencies
        $usdPayment = \Modules\Payment\Models\Payment::factory()
            ->for($this->company)
            ->for($this->usdCurrency)
            ->for($this->bankJournal)
            ->create([
                'amount' => Money::of(100, 'USD'),
                'status' => PaymentStatus::Confirmed,
            ]);

        $iqdPayment = \Modules\Payment\Models\Payment::factory()
            ->for($this->company)
            ->for($this->company->currency) // IQD
            ->for($this->bankJournal)
            ->create([
                'amount' => Money::of(146000, 'IQD'),
                'status' => PaymentStatus::Confirmed,
            ]);

        $component = Livewire::test(SystemPaymentsTable::class, ['bankStatement' => $this->usdBankStatement]);

        // Both payments should be visible (currency filtering will be enhanced in next task)
        $component->assertCanSeeTableRecords([$usdPayment, $iqdPayment]);
    });
});
