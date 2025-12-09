<?php

use Brick\Money\Money;
use Filament\Forms\Components\Field;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Payments\Pages\CreatePayment;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Payments\Pages\EditPayment;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Payments\Pages\ListPayments;
use Modules\Accounting\Models\Journal;
use Modules\Foundation\Models\Partner;
use Modules\Payment\Enums\Payments\PaymentMethod;
use Modules\Payment\Enums\Payments\PaymentStatus;
use Modules\Payment\Enums\Payments\PaymentType;
use Modules\Payment\Models\Payment;
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

    // Walk through components recursively to find the field, since the form may contain Groups/Sections
    $walker = function ($component) use (&$walker, &$paymentMethodField) {
        if ($paymentMethodField) {
            return;
        }
        if ($component instanceof Field) {
            if ($component->getName() === 'payment_method') {
                $paymentMethodField = $component;

                return;
            }
        }
        if (is_object($component) && method_exists($component, 'getChildComponents')) {
            foreach ($component->getChildComponents() as $child) {
                $walker($child);
            }
        }
    };

    foreach ($form->getComponents() as $componentNode) {
        $walker($componentNode);
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

    $paymentData = [
        'journal_id' => $journal->id,
        'currency_id' => $this->company->currency_id,
        'payment_date' => now()->format('Y-m-d'),
        'reference' => 'TEST-001',
        'payment_type' => PaymentType::Outbound->value,
        'payment_method' => PaymentMethod::BankTransfer->value,
        'paid_to_from_partner_id' => $partner->id,
        'amount' => '1000.00',

    ];

    livewire(CreatePayment::class)
        ->fillForm($paymentData)
        ->call('create')
        ->assertHasNoFormErrors();

    $created = Payment::where('reference', 'TEST-001')->first();
    expect($created)
        ->payment_method->toBe(PaymentMethod::BankTransfer)
        ->and($created->payment_type)->toBe(PaymentType::Outbound);
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

    $payment = Payment::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $journal->id,
        'currency_id' => $this->company->currency_id,
        'paid_to_from_partner_id' => $partner->id,
        'payment_method' => PaymentMethod::Manual,

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
            'paid_to_from_partner_id' => $partner->id,
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
