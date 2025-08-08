<?php

use App\Models\User;
use App\Models\Company;
use App\Models\BankStatement;
use App\Models\Payment;
use App\Models\Account;
use App\Models\Journal;
use App\Models\Partner;
use App\Livewire\Accounting\SystemPaymentsTable;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->for($this->company)->create();
    $this->actingAs($this->user);

    $this->currency = $this->company->currency;

    // Create required accounts for reconciliation
    $this->bankAccount = Account::factory()
        ->for($this->company)
        ->create(['type' => 'asset', 'name' => 'Bank Account']);

    $this->outstandingAccount = Account::factory()
        ->for($this->company)
        ->create(['type' => 'asset', 'name' => 'Outstanding Receipts']);

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

describe('SystemPaymentsTable Livewire Component', function () {
    it('can mount with bank statement', function () {
        Livewire::test(SystemPaymentsTable::class, ['bankStatement' => $this->bankStatement])
            ->assertSet('bankStatement.id', $this->bankStatement->id)
            ->assertViewIs('livewire.accounting.system-payments-table');
    });

    it('displays only unreconciled confirmed payments', function () {
        $partner = Partner::factory()->for($this->company)->create();

        $confirmedPayment = Payment::factory()
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
            ->create([
                'status' => 'Reconciled',
                'amount' => Money::of(200, $this->currency->code),
            ]);

        $draftPayment = Payment::factory()
            ->for($this->company)
            ->for($this->currency)
            ->for($this->bankJournal)
            ->create([
                'status' => 'draft',
                'amount' => Money::of(300, $this->currency->code),
            ]);

        Livewire::test(SystemPaymentsTable::class, ['bankStatement' => $this->bankStatement])
            ->assertSee($partner->name)
            ->assertDontSee('Reconciled Payment')
            ->assertDontSee('Draft Payment');
    });

    it('can toggle payment selection', function () {
        $payment = Payment::factory()
            ->for($this->company)
            ->for($this->currency)
            ->for($this->bankJournal)
            ->create(['status' => 'confirmed']);

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

    it('emits selection changed event when toggling payments', function () {
        $payment = Payment::factory()
            ->for($this->company)
            ->for($this->currency)
            ->for($this->bankJournal)
            ->create([
                'amount' => Money::of(100, $this->currency->code),
                'payment_type' => 'inbound',
                'status' => 'confirmed',
            ]);

        Livewire::test(SystemPaymentsTable::class, ['bankStatement' => $this->bankStatement])
            ->call('togglePayment', $payment->id)
            ->assertDispatched('payment-selection-changed', [
                'selectedIds' => [$payment->id],
                'total' => 100000, // 100.000 IQD in minor units
                'currency' => $this->currency->code,
            ]);
    });

    it('calculates correct total for inbound payments', function () {
        $inboundPayment = Payment::factory()
            ->for($this->company)
            ->for($this->currency)
            ->for($this->bankJournal)
            ->create([
                'amount' => Money::of(100, $this->currency->code),
                'payment_type' => 'inbound',
                'status' => 'confirmed',
            ]);

        Livewire::test(SystemPaymentsTable::class, ['bankStatement' => $this->bankStatement])
            ->call('togglePayment', $inboundPayment->id)
            ->assertDispatched('payment-selection-changed', [
                'selectedIds' => [$inboundPayment->id],
                'total' => 100000, // Positive for inbound
                'currency' => $this->currency->code,
            ]);
    });

    it('calculates correct total for outbound payments', function () {
        $outboundPayment = Payment::factory()
            ->for($this->company)
            ->for($this->currency)
            ->for($this->bankJournal)
            ->create([
                'amount' => Money::of(100, $this->currency->code),
                'payment_type' => 'outbound',
                'status' => 'confirmed',
            ]);

        Livewire::test(SystemPaymentsTable::class, ['bankStatement' => $this->bankStatement])
            ->call('togglePayment', $outboundPayment->id)
            ->assertDispatched('payment-selection-changed', [
                'selectedIds' => [$outboundPayment->id],
                'total' => -100000, // Negative for outbound
                'currency' => $this->currency->code,
            ]);
    });

    it('calculates correct total for mixed payment types', function () {
        $inboundPayment = Payment::factory()
            ->for($this->company)
            ->for($this->currency)
            ->for($this->bankJournal)
            ->create([
                'amount' => Money::of(150, $this->currency->code),
                'payment_type' => 'inbound',
                'status' => 'confirmed',
            ]);

        $outboundPayment = Payment::factory()
            ->for($this->company)
            ->for($this->currency)
            ->for($this->bankJournal)
            ->create([
                'amount' => Money::of(50, $this->currency->code),
                'payment_type' => 'outbound',
                'status' => 'confirmed',
            ]);

        $component = Livewire::test(SystemPaymentsTable::class, ['bankStatement' => $this->bankStatement]);

        // Select both payments
        $component->call('togglePayment', $inboundPayment->id);
        $component->call('togglePayment', $outboundPayment->id);

        // Should emit event with net total (150 - 50 = 100)
        $component->assertDispatched('payment-selection-changed', [
            'selectedIds' => [$inboundPayment->id, $outboundPayment->id],
            'total' => 100000, // 100.000 IQD in minor units (150 - 50)
            'currency' => $this->currency->code,
        ]);
    });

    it('displays payment information correctly', function () {
        $partner = Partner::factory()->for($this->company)->create(['name' => 'Test Partner']);

        $payment = Payment::factory()
            ->for($this->company)
            ->for($this->currency)
            ->for($this->bankJournal)
            ->for($partner, 'partner')
            ->create([
                'amount' => Money::of(250, $this->currency->code),
                'payment_type' => 'inbound',
                'status' => 'confirmed',
                'reference' => 'PAY-001',
            ]);

        Livewire::test(SystemPaymentsTable::class, ['bankStatement' => $this->bankStatement])
            ->assertSee('Test Partner')
            ->assertSee('Inbound') // Payment type is displayed
            ->assertSee('250.000'); // Amount display
    });

    it('filters payments by company', function () {
        $otherCompany = Company::factory()->create();

        $companyPartner = Partner::factory()->for($this->company)->create(['name' => 'Company Partner']);
        $otherPartner = Partner::factory()->for($otherCompany)->create(['name' => 'Other Partner']);

        $companyPayment = Payment::factory()
            ->for($this->company)
            ->for($this->currency)
            ->for($this->bankJournal)
            ->for($companyPartner, 'partner')
            ->create([
                'status' => 'confirmed',
                'reference' => 'COMPANY-PAY',
            ]);

        $otherCompanyPayment = Payment::factory()
            ->for($otherCompany)
            ->for($this->currency)
            ->for($this->bankJournal)
            ->for($otherPartner, 'partner')
            ->create([
                'status' => 'confirmed',
                'reference' => 'OTHER-PAY',
            ]);

        Livewire::test(SystemPaymentsTable::class, ['bankStatement' => $this->bankStatement])
            ->assertSee('Company Partner')
            ->assertDontSee('Other Partner');
    });

    it('shows empty state when no unreconciled payments exist', function () {
        // Create only reconciled payments
        Payment::factory()
            ->for($this->company)
            ->for($this->currency)
            ->for($this->bankJournal)
            ->create(['status' => 'Reconciled']);

        Livewire::test(SystemPaymentsTable::class, ['bankStatement' => $this->bankStatement])
            ->assertSee('No unreconciled payments found');
    });
});
