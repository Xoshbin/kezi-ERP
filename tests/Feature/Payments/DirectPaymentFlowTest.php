<?php

use App\Actions\Payments\CreatePaymentAction;
use App\DataTransferObjects\Payments\CreatePaymentDTO;
use App\Enums\Accounting\JournalType;
use App\Enums\Payments\PaymentMethod;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Payments\PaymentType;
use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Partner;
use App\Services\PaymentService;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

test('a loan payment creates the correct journal entry', function () {
    // Arrange: Create necessary accounts and partners
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);
    $loanAccount = Account::factory()->for($this->company)->create(['name' => 'Loans Payable']);
    $partner = Partner::factory()->for($this->company)->create();

    $paymentDTO = new CreatePaymentDTO(
        company_id: $this->company->id,
        journal_id: $bankJournal->id,
        currency_id: $this->company->currency_id,
        payment_date: now()->toDateString(),
        payment_purpose: PaymentPurpose::Loan,
        payment_type: PaymentType::Inbound,
        payment_method: PaymentMethod::BankTransfer,
        partner_id: $partner->id,
        amount: Money::of(10000, $this->company->currency->code),
        counterpart_account_id: $loanAccount->id,
        document_links: [],
        reference: 'Loan from Bank'
    );

    // Act: Create and confirm the payment
    $payment = app(CreatePaymentAction::class)->execute($paymentDTO, $this->user);
    app(PaymentService::class)->confirm($payment, $this->user);
    $payment->refresh();

    // Assert: Payment is created correctly
    expect($payment->payment_purpose)->toBe(PaymentPurpose::Loan);
    expect($payment->payment_type)->toBe(PaymentType::Inbound);
    expect($payment->counterpart_account_id)->toBe($loanAccount->id);
    expect($payment->amount->getAmount()->toFloat())->toBe(10000.0);
    expect($payment->status)->toBe(PaymentStatus::Confirmed);

    // Assert: Journal entry is created with correct accounting
    expect($payment->journal_entry_id)->not->toBeNull();
    $journalEntry = JournalEntry::find($payment->journal_entry_id);
    expect($journalEntry)->not->toBeNull();
    expect($journalEntry->lines)->toHaveCount(2);

    // Assert: Correct debit/credit entries (Inbound loan: Debit Bank, Credit Loan Payable)
    $bankLine = $journalEntry->lines->where('account_id', $bankJournal->default_debit_account_id)->first();
    $loanLine = $journalEntry->lines->where('account_id', $loanAccount->id)->first();

    expect($bankLine)->not->toBeNull();
    expect($bankLine->debit->getAmount()->toFloat())->toBe(10000.0);
    expect($bankLine->credit->isZero())->toBeTrue();

    expect($loanLine)->not->toBeNull();
    expect($loanLine->credit->getAmount()->toFloat())->toBe(10000.0);
    expect($loanLine->debit->isZero())->toBeTrue();
});

test('a capital injection payment creates the correct journal entry', function () {
    // Arrange
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);
    $equityAccount = Account::factory()->for($this->company)->create(['name' => 'Owner Equity']);
    $partner = Partner::factory()->for($this->company)->create();

    $paymentDTO = new CreatePaymentDTO(
        company_id: $this->company->id,
        journal_id: $bankJournal->id,
        currency_id: $this->company->currency_id,
        payment_date: now()->toDateString(),
        payment_purpose: PaymentPurpose::CapitalInjection,
        payment_type: PaymentType::Inbound,
        payment_method: PaymentMethod::BankTransfer,
        partner_id: $partner->id,
        amount: Money::of(25000, $this->company->currency->code),
        counterpart_account_id: $equityAccount->id,
        document_links: [],
        reference: 'Owner Capital Investment'
    );

    // Act
    $payment = app(CreatePaymentAction::class)->execute($paymentDTO, $this->user);
    app(PaymentService::class)->confirm($payment, $this->user);
    $payment->refresh();

    // Assert: Payment details
    expect($payment->payment_purpose)->toBe(PaymentPurpose::CapitalInjection);
    expect($payment->counterpart_account_id)->toBe($equityAccount->id);

    // Assert: Journal entry accounting (Inbound capital: Debit Bank, Credit Equity)
    $journalEntry = JournalEntry::find($payment->journal_entry_id);
    $bankLine = $journalEntry->lines->where('account_id', $bankJournal->default_debit_account_id)->first();
    $equityLine = $journalEntry->lines->where('account_id', $equityAccount->id)->first();

    expect($bankLine->debit->getAmount()->toFloat())->toBe(25000.0);
    expect($equityLine->credit->getAmount()->toFloat())->toBe(25000.0);
});

test('an expense claim payment creates the correct journal entry', function () {
    // Arrange
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);
    $expenseAccount = Account::factory()->for($this->company)->create(['name' => 'Office Expenses']);
    $partner = Partner::factory()->for($this->company)->create();

    $paymentDTO = new CreatePaymentDTO(
        company_id: $this->company->id,
        journal_id: $bankJournal->id,
        currency_id: $this->company->currency_id,
        payment_date: now()->toDateString(),
        payment_purpose: PaymentPurpose::ExpenseClaim,
        payment_type: PaymentType::Outbound,
        payment_method: PaymentMethod::BankTransfer,
        partner_id: $partner->id,
        amount: Money::of(500, $this->company->currency->code),
        counterpart_account_id: $expenseAccount->id,
        document_links: [],
        reference: 'Employee Expense Reimbursement'
    );

    // Act
    $payment = app(CreatePaymentAction::class)->execute($paymentDTO, $this->user);
    app(PaymentService::class)->confirm($payment, $this->user);
    $payment->refresh();

    // Assert: Payment details
    expect($payment->payment_purpose)->toBe(PaymentPurpose::ExpenseClaim);
    expect($payment->payment_type)->toBe(PaymentType::Outbound);

    // Assert: Journal entry accounting (Outbound expense: Debit Expense, Credit Bank)
    $journalEntry = JournalEntry::find($payment->journal_entry_id);
    $expenseLine = $journalEntry->lines->where('account_id', $expenseAccount->id)->first();
    $bankLine = $journalEntry->lines->where('account_id', $bankJournal->default_debit_account_id)->first();

    expect($expenseLine->debit->getAmount()->toFloat())->toBe(500.0);
    expect($bankLine->credit->getAmount()->toFloat())->toBe(500.0);
});

test('settlement payments still work as before', function () {
    // This test ensures backward compatibility with existing settlement functionality
    // The detailed settlement tests are already covered in existing test files

    // Arrange
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);
    $partner = Partner::factory()->for($this->company)->create();

    $paymentDTO = new CreatePaymentDTO(
        company_id: $this->company->id,
        journal_id: $bankJournal->id,
        currency_id: $this->company->currency_id,
        payment_date: now()->toDateString(),
        payment_purpose: PaymentPurpose::Settlement,
        payment_type: PaymentType::Inbound,
        payment_method: PaymentMethod::BankTransfer,
        partner_id: null,
        amount: null,
        counterpart_account_id: null,
        document_links: [], // Would normally have document links, but testing the purpose assignment
        reference: 'Settlement Payment'
    );

    // Act
    expect(fn () => app(CreatePaymentAction::class)->execute($paymentDTO, $this->user))
        ->toThrow(\InvalidArgumentException::class, 'Settlement payments must be linked to at least one document.');

    // Assert: The validation works correctly for settlement payments
    expect(true)->toBeTrue(); // Test passes if exception is thrown
});
