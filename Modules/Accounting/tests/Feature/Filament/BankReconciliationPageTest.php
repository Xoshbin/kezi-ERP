<?php

use Brick\Money\Money;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\BankStatements\Pages\BankReconciliation;
use Modules\Accounting\Livewire\Accounting\BankReconciliationMatcher;
use Modules\Accounting\Models\BankStatement;
use Modules\Accounting\Models\Journal;
use Modules\Payment\Enums\Payments\PaymentStatus;
use Modules\Payment\Enums\Payments\PaymentType;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setTenant($this->company);

    // Setup for reconciliation:
    // 1. Create a Bank Journal
    $this->bankJournal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Bank 1',
        'type' => 'bank',
        'short_code' => 'BNK1',
    ]);

    // 2. Create Bank Statement
    $this->bankStatement = BankStatement::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $this->bankJournal->id,
        'date' => now(),
        'starting_balance' => 0,
        'ending_balance' => 100000, // 1000.00
        'currency_id' => $this->company->currency_id,
        'reference' => 'REF-001',
    ]);
});

describe('BankReconciliationPage', function () {
    it('cannot access reconciliation page if disabled', function () {
        $this->company->update(['enable_reconciliation' => false]);

        $this->actingAs($this->user)
            ->get(BankReconciliation::getUrl(['record' => $this->bankStatement->id], tenant: $this->company))
            ->assertForbidden();
    });

    it('can access reconciliation page if enabled', function () {
        $this->company->update(['enable_reconciliation' => true]);

        $this->actingAs($this->user)
            ->get(BankReconciliation::getUrl(['record' => $this->bankStatement->id], tenant: $this->company))
            ->assertSuccessful()
            ->assertSee('Reconcile Bank Statement')
            ->assertSee('REF-001');
    });

    it('renders the reconciliation matcher component', function () {
        $this->company->update(['enable_reconciliation' => true]);

        livewire(BankReconciliationMatcher::class, ['bankStatementId' => $this->bankStatement->id])
            ->assertSet('bankStatementId', $this->bankStatement->id)
            ->assertSeeHtml('bank-reconciliation-matcher');
    });

    it('can successfully reconcile transactions', function () {
        $this->company->update(['enable_reconciliation' => true]);

        // 1. Create a Payment (Inbound for Customer)
        $payment = \Modules\Payment\Models\Payment::factory()->create([
            'company_id' => $this->company->id,
            'amount' => Money::of(1000, $this->company->currency->code),
            'currency_id' => $this->company->currency_id,
            'status' => PaymentStatus::Confirmed,
            'payment_type' => PaymentType::Inbound,
        ]);

        // 2. Create Bank Statement Line
        $statementLine = \Modules\Accounting\Models\BankStatementLine::factory()->create([
            'bank_statement_id' => $this->bankStatement->id,
            'amount' => Money::of(1000, $this->company->currency->code),
            'partner_id' => $payment->paid_to_from_partner_id,
        ]);

        // 3. Test the Matcher component
        livewire(BankReconciliationMatcher::class, ['bankStatementId' => $this->bankStatement->id])
            // Simulate 'bank-selection-changed' event
            ->dispatch('bank-selection-changed', [
                'selectedIds' => [$statementLine->id],
                'total' => $statementLine->amount->getMinorAmount()->toInt(),
                'currency' => $this->company->currency->code,
            ])
            // Simulate 'payment-selection-changed' event
            ->dispatch('payment-selection-changed', [
                'selectedIds' => [$payment->id],
                'total' => $payment->amount->getMinorAmount()->toInt(),
                'currency' => $this->company->currency->code,
            ])
            // Assert that the difference is zero
            ->assertSet('bankTotal', $statementLine->amount)
            ->assertSet('systemTotal', $payment->amount)
            // Call reconcile
            ->call('reconcile')
            ->assertNotified()
            ->assertDispatched('refresh-tables');

        // Verify reconciliation in database
        $statementLine->refresh();
        expect($statementLine->payment_id)->toBe($payment->id);
    });
});
