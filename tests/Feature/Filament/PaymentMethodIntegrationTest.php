<?php

use App\Enums\Payments\PaymentMethod;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Payments\PaymentType;
use App\Filament\Clusters\Accounting\Resources\Payments\Pages\CreatePayment;
use App\Filament\Clusters\Accounting\Resources\Payments\Pages\EditPayment;
use App\Filament\Clusters\Accounting\Resources\Payments\Pages\ListPayments;
use App\Models\Account;
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
});

it('includes payment method field in form', function () {
    livewire(CreatePayment::class)
        ->assertFormFieldExists('payment_method');
});

it('has correct payment method options', function () {
    $component = livewire(CreatePayment::class);

    // Get the form schema to check payment_method options
    $form = $component->instance()->form;
    $paymentMethodField = null;

    foreach ($form->getComponents() as $section) {
        foreach ($section->getChildComponents() as $field) {
            if ($field->getName() === 'payment_method') {
                $paymentMethodField = $field;
                break 2;
            }
        }
    }

    expect($paymentMethodField)->not->toBeNull();

    $options = $paymentMethodField->getOptions();
    $expectedOptions = collect(PaymentMethod::cases())
        ->mapWithKeys(fn ($case) => [$case->value => $case->label()]);

    expect($options)->toEqual($expectedOptions->toArray());
});

it('can create payment with payment method', function () {
    $partner = Partner::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $journal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
    ]);

    $account = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => \App\Enums\Accounting\AccountType::Expense->value,
    ]);

    $paymentData = [
        'journal_id' => $journal->id,
        'currency_id' => $this->company->currency_id,
        'payment_date' => now()->format('Y-m-d'),
        'reference' => 'TEST-001',
        'payment_type' => PaymentType::Outbound->value,
        'payment_method' => PaymentMethod::BankTransfer->value,
        'payment_purpose' => PaymentPurpose::Loan->value,
        'partner_id' => $partner->id,
        'amount' => '1000.00',
        'counterpart_account_id' => $account->id,
    ];

    livewire(CreatePayment::class)
        ->fillForm($paymentData)
        ->call('create')
        ->assertHasNoFormErrors();

    expect(\App\Models\Payment::where('reference', 'TEST-001')->first())
        ->payment_method->toBe(PaymentMethod::BankTransfer)
        ->payment_type->toBe(PaymentType::Outbound);
});

it('displays payment method column in table', function () {
    $partner = Partner::factory()->create(['company_id' => $this->company->id]);
    $journal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
    ]);

    $payment = Payment::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $journal->id,
        'currency_id' => $this->company->currency_id,
        'paid_to_from_partner_id' => $partner->id,
        'payment_method' => PaymentMethod::CreditCard,
        'amount' => Money::of(500, $this->company->currency->code),
    ]);

    livewire(ListPayments::class)
        ->assertCanSeeTableRecords([$payment])
        ->assertTableColumnExists('payment_method');
});

it('displays payment method badge correctly', function () {
    $partner = Partner::factory()->create(['company_id' => $this->company->id]);
    $journal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
    ]);

    Payment::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $journal->id,
        'currency_id' => $this->company->currency_id,
        'paid_to_from_partner_id' => $partner->id,
        'payment_method' => PaymentMethod::Check,
        'amount' => Money::of(750, $this->company->currency->code),
    ]);

    livewire(ListPayments::class)
        ->assertSee(PaymentMethod::Check->label());
});

it('can edit payment method', function () {
    $partner = Partner::factory()->create(['company_id' => $this->company->id]);
    $journal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
    ]);

    $account = \App\Models\Account::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $payment = Payment::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $journal->id,
        'currency_id' => $this->company->currency_id,
        'paid_to_from_partner_id' => $partner->id,
        'payment_method' => PaymentMethod::Manual,
        'payment_purpose' => \App\Enums\Payments\PaymentPurpose::Loan, // Use loan instead of settlement
        'counterpart_account_id' => $account->id, // Provide counterpart account for non-settlement
        'status' => PaymentStatus::Draft,
        'amount' => Money::of(300, $this->company->currency->code),
    ]);

    livewire(EditPayment::class, ['record' => $payment->getRouteKey()])
        ->fillForm([
            'journal_id' => $journal->id,
            'currency_id' => $this->company->currency_id,
            'payment_date' => $payment->payment_date->format('Y-m-d'),
            'payment_type' => $payment->payment_type->value,
            'payment_method' => PaymentMethod::WireTransfer->value,
            'partner_id' => $partner->id,
            'amount' => '300.00',
            'reference' => $payment->reference,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $payment->refresh();
    expect($payment->payment_method)->toBe(PaymentMethod::WireTransfer);
});

it('can search by payment method in table', function () {
    $partner = Partner::factory()->create(['company_id' => $this->company->id]);
    $journal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
    ]);

    $checkPayment = Payment::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $journal->id,
        'currency_id' => $this->company->currency_id,
        'paid_to_from_partner_id' => $partner->id,
        'payment_method' => PaymentMethod::Check,
        'reference' => 'CHECK-001',
        'amount' => Money::of(100, $this->company->currency->code),
    ]);

    $wirePayment = Payment::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $journal->id,
        'currency_id' => $this->company->currency_id,
        'paid_to_from_partner_id' => $partner->id,
        'payment_method' => PaymentMethod::WireTransfer,
        'reference' => 'WIRE-001',
        'amount' => Money::of(200, $this->company->currency->code),
    ]);

    livewire(ListPayments::class)
        ->searchTable(PaymentMethod::Check->value)
        ->assertCanSeeTableRecords([$checkPayment])
        ->assertCanNotSeeTableRecords([$wirePayment]);
});
