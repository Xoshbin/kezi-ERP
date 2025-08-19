<?php

use App\Services\Payments\Strategies\DirectPaymentStrategy;
use App\DataTransferObjects\Payments\CreatePaymentDTO;
use App\DataTransferObjects\Payments\UpdatePaymentDTO;
use App\Models\Payment;
use App\Models\Account;
use App\Models\Partner;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentType;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->strategy = new DirectPaymentStrategy();
});

test('it handles direct payment creation correctly', function () {
    // Arrange
    $partner = Partner::factory()->for($this->company)->create();
    $loanAccount = Account::factory()->for($this->company)->create(['name' => 'Loans Payable']);
    
    $payment = Payment::factory()->for($this->company)->create([
        'payment_purpose' => PaymentPurpose::Loan,
        'payment_type' => PaymentType::Inbound,
        'counterpart_account_id' => $loanAccount->id,
        'paid_to_from_partner_id' => $partner->id,
    ]);

    $dto = new CreatePaymentDTO(
        company_id: $this->company->id,
        journal_id: $payment->journal_id,
        currency_id: $payment->currency_id,
        payment_date: $payment->payment_date->toDateString(),
        payment_purpose: PaymentPurpose::Loan,
        payment_type: PaymentType::Inbound,
        partner_id: $partner->id,
        amount: Money::of(1000, $this->company->currency->code),
        counterpart_account_id: $loanAccount->id,
        document_links: [],
        reference: 'Loan Payment'
    );

    // Act
    $this->strategy->executeCreate($payment, $dto);

    // Assert
    // For direct payments, the strategy doesn't create document links
    expect($payment->paymentDocumentLinks)->toHaveCount(0);
    expect($payment->counterpart_account_id)->toBe($loanAccount->id);
    expect($payment->payment_purpose)->toBe(PaymentPurpose::Loan);
});

test('it handles capital injection payment correctly', function () {
    // Arrange
    $partner = Partner::factory()->for($this->company)->create();
    $equityAccount = Account::factory()->for($this->company)->create(['name' => 'Owner Equity']);
    
    $payment = Payment::factory()->for($this->company)->create([
        'payment_purpose' => PaymentPurpose::CapitalInjection,
        'payment_type' => PaymentType::Inbound,
        'counterpart_account_id' => $equityAccount->id,
        'paid_to_from_partner_id' => $partner->id,
    ]);

    $dto = new CreatePaymentDTO(
        company_id: $this->company->id,
        journal_id: $payment->journal_id,
        currency_id: $payment->currency_id,
        payment_date: $payment->payment_date->toDateString(),
        payment_purpose: PaymentPurpose::CapitalInjection,
        payment_type: PaymentType::Inbound,
        partner_id: $partner->id,
        amount: Money::of(5000, $this->company->currency->code),
        counterpart_account_id: $equityAccount->id,
        document_links: [],
        reference: 'Capital Injection'
    );

    // Act
    $this->strategy->executeCreate($payment, $dto);

    // Assert
    expect($payment->paymentDocumentLinks)->toHaveCount(0);
    expect($payment->counterpart_account_id)->toBe($equityAccount->id);
    expect($payment->payment_purpose)->toBe(PaymentPurpose::CapitalInjection);
});

test('it handles expense claim payment correctly', function () {
    // Arrange
    $partner = Partner::factory()->for($this->company)->create();
    $expenseAccount = Account::factory()->for($this->company)->create(['name' => 'Office Expenses']);
    
    $payment = Payment::factory()->for($this->company)->create([
        'payment_purpose' => PaymentPurpose::ExpenseClaim,
        'payment_type' => PaymentType::Outbound,
        'counterpart_account_id' => $expenseAccount->id,
        'paid_to_from_partner_id' => $partner->id,
    ]);

    $dto = new UpdatePaymentDTO(
        payment: $payment,
        company_id: $this->company->id,
        journal_id: $payment->journal_id,
        currency_id: $payment->currency_id,
        payment_date: $payment->payment_date->toDateString(),
        payment_purpose: PaymentPurpose::ExpenseClaim,
        payment_type: PaymentType::Outbound,
        partner_id: $partner->id,
        amount: Money::of(250, $this->company->currency->code),
        counterpart_account_id: $expenseAccount->id,
        document_links: [],
        reference: 'Expense Reimbursement',
        updated_by_user_id: $this->user->id
    );

    // Act
    $this->strategy->executeUpdate($payment, $dto);

    // Assert
    expect($payment->paymentDocumentLinks)->toHaveCount(0);
    expect($payment->counterpart_account_id)->toBe($expenseAccount->id);
    expect($payment->payment_purpose)->toBe(PaymentPurpose::ExpenseClaim);
});
