<?php

namespace Kezi\Accounting\Tests\Feature\Actions\Accounting;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Kezi\Accounting\Actions\Accounting\CreateJournalEntryAction;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Kezi\Accounting\Models\Account;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

it('throws validation exception when creating journal entry with no lines', function () {
    $dto = new CreateJournalEntryDTO(
        company_id: $this->company->id,
        journal_id: $this->company->default_sales_journal_id,
        currency_id: $this->company->currency_id,
        entry_date: now()->toDateString(),
        reference: 'TEST-NO-LINES',
        description: 'Test entry with no lines',
        created_by_user_id: $this->user->id,
        is_posted: false,
        lines: []
    );

    $action = app(CreateJournalEntryAction::class);

    try {
        $action->execute($dto);
        $this->fail('Expected ValidationException was not thrown for empty lines.');
    } catch (ValidationException $e) {
        expect($e->validator->errors()->has('lines'))->toBeTrue();
    }
});

it('throws validation exception when creating journal entry with only one line', function () {
    $account = Account::factory()->for($this->company)->create();
    $currencyCode = $this->company->currency->code;
    $amount = Money::of('100', $currencyCode);
    $zero = Money::zero($currencyCode); // Ensure $zero uses Money object

    $line = new CreateJournalEntryLineDTO(
        account_id: $account->id,
        debit: $amount,
        credit: $zero,
        description: 'Line 1',
        partner_id: null,
        analytic_account_id: null,
    );

    $dto = new CreateJournalEntryDTO(
        company_id: $this->company->id,
        journal_id: $this->company->default_sales_journal_id,
        currency_id: $this->company->currency_id,
        entry_date: now()->toDateString(),
        reference: 'TEST-ONE-LINE',
        description: 'Test entry with one line',
        created_by_user_id: $this->user->id,
        is_posted: false,
        lines: [$line]
    );

    $action = app(CreateJournalEntryAction::class);

    try {
        $action->execute($dto);
        $this->fail('Expected ValidationException was not thrown for single line.');
    } catch (ValidationException $e) {
        expect($e->validator->errors()->has('lines'))->toBeTrue();
    }
});

it('creates journal entry successfully with two balanced lines', function () {
    $accountA = Account::factory()->for($this->company)->create();
    $accountB = Account::factory()->for($this->company)->create();
    $currencyCode = $this->company->currency->code;
    $amount = Money::of('100', $currencyCode);
    $zero = Money::zero($currencyCode);

    $line1 = new CreateJournalEntryLineDTO(
        account_id: $accountA->id,
        debit: $amount,
        credit: $zero,
        description: 'Line 1',
        partner_id: null,
        analytic_account_id: null,
    );

    $line2 = new CreateJournalEntryLineDTO(
        account_id: $accountB->id,
        debit: $zero,
        credit: $amount,
        description: 'Line 2',
        partner_id: null,
        analytic_account_id: null,
    );

    $dto = new CreateJournalEntryDTO(
        company_id: $this->company->id,
        journal_id: $this->company->default_sales_journal_id,
        currency_id: $this->company->currency_id,
        entry_date: now()->toDateString(),
        reference: 'TEST-VALID',
        description: 'Test valid entry',
        created_by_user_id: $this->user->id,
        is_posted: false,
        lines: [$line1, $line2]
    );

    $action = app(CreateJournalEntryAction::class);
    $journalEntry = $action->execute($dto);

    expect($journalEntry)->toBeInstanceOf(\Kezi\Accounting\Models\JournalEntry::class)
        ->and($journalEntry->lines)->toHaveCount(2);
});

it('throws validation exception when creating journal entry with no date', function () {
    $accountA = Account::factory()->for($this->company)->create();
    $accountB = Account::factory()->for($this->company)->create();
    $currencyCode = $this->company->currency->code;
    $amount = Money::of('100', $currencyCode);
    $zero = Money::zero($currencyCode);

    $line1 = new CreateJournalEntryLineDTO(
        account_id: $accountA->id,
        debit: $amount,
        credit: $zero,
        description: 'Line 1',
        partner_id: null,
        analytic_account_id: null,
    );

    $line2 = new CreateJournalEntryLineDTO(
        account_id: $accountB->id,
        debit: $zero,
        credit: $amount,
        description: 'Line 2',
        partner_id: null,
        analytic_account_id: null,
    );

    // DTO requires a string for date, but we can pass an empty string to simulate bad input
    // or just assume the Action checks specifically for empty/null if we bypass DTO strictness or if DTO allows empty string

    // Actually, DTO defines entry_date as string. But normally we pass 'Y-m-d'.
    // If we pass empty string:
    $dto = new CreateJournalEntryDTO(
        company_id: $this->company->id,
        journal_id: $this->company->default_sales_journal_id,
        currency_id: $this->company->currency_id,
        entry_date: '', // Testing empty date
        reference: 'TEST-NO-DATE',
        description: 'Test entry with no date',
        created_by_user_id: $this->user->id,
        is_posted: false,
        lines: [$line1, $line2]
    );

    $action = app(CreateJournalEntryAction::class);

    try {
        $action->execute($dto);
        $this->fail('Expected ValidationException was not thrown for empty date.');
    } catch (ValidationException $e) {
        expect($e->validator->errors()->has('entry_date'))->toBeTrue();
    }
});

it('allows creating journal entry with null reference', function () {
    $accountA = Account::factory()->for($this->company)->create();
    $accountB = Account::factory()->for($this->company)->create();
    $currencyCode = $this->company->currency->code;
    $amount = Money::of('100', $currencyCode);
    $zero = Money::zero($currencyCode);

    $line1 = new CreateJournalEntryLineDTO(
        account_id: $accountA->id,
        debit: $amount,
        credit: $zero,
        description: 'Line 1',
        partner_id: null,
        analytic_account_id: null,
    );

    $line2 = new CreateJournalEntryLineDTO(
        account_id: $accountB->id,
        debit: $zero,
        credit: $amount,
        description: 'Line 2',
        partner_id: null,
        analytic_account_id: null,
    );

    $dto = new CreateJournalEntryDTO(
        company_id: $this->company->id,
        journal_id: $this->company->default_sales_journal_id,
        currency_id: $this->company->currency->code === 'USD' ? 1 : $this->company->currency_id, // Ensure valid currency ID
        entry_date: now()->toDateString(),
        reference: null,
        description: 'Test entry with no reference',
        created_by_user_id: $this->user->id,
        is_posted: false,
        lines: [$line1, $line2]
    );

    $action = app(CreateJournalEntryAction::class);

    $journalEntry = $action->execute($dto);

    expect($journalEntry)->toBeInstanceOf(\Kezi\Accounting\Models\JournalEntry::class)
        ->and($journalEntry->reference)->toBeNull()
        ->and($journalEntry->entry_number)->toBeNull();
});
