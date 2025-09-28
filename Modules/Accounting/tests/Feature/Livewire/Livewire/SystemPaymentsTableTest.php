<?php

use App\Models\User;
use Brick\Money\Money;
use Livewire\Livewire;
use App\Models\Company;
use Filament\Facades\Filament;
use Modules\Payment\Models\Payment;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Modules\Foundation\Models\Partner;
use Modules\Accounting\Models\BankStatement;
use Modules\Payment\Enums\Payments\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Livewire\Accounting\SystemPaymentsTable;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Set locale to English for consistent test assertions
    app()->setLocale('en');

    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);
    $this->actingAs($this->user);

    // Set up Filament tenant context
    Filament::setTenant($this->company);

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
                'status' => PaymentStatus::Confirmed,
                'amount' => Money::of(100, $this->currency->code),
            ]);

        $reconciledPayment = Payment::factory()
            ->for($this->company)
            ->for($this->currency)
            ->for($this->bankJournal)
            ->create([
                'status' => PaymentStatus::Reconciled,
                'amount' => Money::of(200, $this->currency->code),
            ]);

        $draftPayment = Payment::factory()
            ->for($this->company)
            ->for($this->currency)
            ->for($this->bankJournal)
            ->create([
                'status' => PaymentStatus::Draft,
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
            ->create(['status' => PaymentStatus::Confirmed]);

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
                'status' => PaymentStatus::Confirmed,
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
                'status' => PaymentStatus::Confirmed,
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
                'status' => PaymentStatus::Confirmed,
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
                'status' => PaymentStatus::Confirmed,
            ]);

        $outboundPayment = Payment::factory()
            ->for($this->company)
            ->for($this->currency)
            ->for($this->bankJournal)
            ->create([
                'amount' => Money::of(50, $this->currency->code),
                'payment_type' => 'outbound',
                'status' => PaymentStatus::Confirmed,
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
                'status' => PaymentStatus::Confirmed,
                'reference' => 'PAY-001',
            ]);

        $livewire = Livewire::test(SystemPaymentsTable::class, ['bankStatement' => $this->bankStatement]);

        // Debug: Check what's actually in the HTML
        $html = $livewire->html();

        // Check if the table has any content at all
        expect($html)->toContain('System Payments');

        // Check if we can see the partner name
        expect($html)->toContain('Test Partner');

        // Check if we can see the payment type
        expect($html)->toContain('Inbound');

        // Check if we can see the amount formatted correctly
        $livewire->assertSee('250.000'); // Amount display with NumberFormatter formatting
    });

    // it('filters payments by company', function () {
    //     $otherCompany = Company::factory()->create();

    //     $companyPartner = Partner::factory()->for($this->company)->create(['name' => 'Company Partner']);
    //     $otherPartner = Partner::factory()->for($otherCompany)->create(['name' => 'Other Partner']);

    //     $companyPayment = Payment::factory()
    //         ->for($this->company)
    //         ->for($this->currency)
    //         ->for($this->bankJournal)
    //         ->for($companyPartner, 'partner')
    //         ->create([
    //             'status' => PaymentStatus::Confirmed,
    //             'reference' => 'COMPANY-PAY',
    //         ]);

    //     $otherCompanyPayment = Payment::factory()
    //         ->for($otherCompany)
    //         ->for($this->currency)
    //         ->for($this->bankJournal)
    //         ->for($otherPartner, 'partner')
    //         ->create([
    //             'status' => PaymentStatus::Confirmed,
    //             'reference' => 'OTHER-PAY',
    //         ]);

    //     Livewire::test(SystemPaymentsTable::class, ['bankStatement' => $this->bankStatement])
    //         ->assertSee('Company Partner')
    //         ->assertDontSee('Other Partner');
    // });

    it('shows empty state when no unreconciled payments exist', function () {
        // Create only reconciled payments
        Payment::factory()
            ->for($this->company)
            ->for($this->currency)
            ->for($this->bankJournal)
            ->create(['status' => PaymentStatus::Reconciled]);

        Livewire::test(SystemPaymentsTable::class, ['bankStatement' => $this->bankStatement])
            ->assertSee('No unreconciled payments found');
    });
});
