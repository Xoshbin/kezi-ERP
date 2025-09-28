<?php

use App\Models\User;
use Brick\Money\Money;
use App\Models\Company;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Modules\Foundation\Models\Partner;
use Modules\Foundation\Models\Currency;
use Tests\Traits\WithConfiguredCompany;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\Reconciliation;
use Modules\Accounting\Models\JournalEntryLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Enums\Reconciliation\ReconciliationType;
use Modules\Accounting\Actions\Reconciliation\MatchJournalItemsAction;
use Modules\Accounting\Exceptions\Reconciliation\PartnerMismatchException;
use Modules\Accounting\Exceptions\Reconciliation\AlreadyReconciledException;
use Modules\Accounting\Exceptions\Reconciliation\AccountNotReconcilableException;
use Modules\Accounting\Exceptions\Reconciliation\ReconciliationDisabledException;
use Modules\Accounting\Exceptions\Reconciliation\UnbalancedReconciliationException;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->action = app(MatchJournalItemsAction::class);
});

test('it throws exception when reconciliation is globally disabled', function () {
    // Work with the existing company setup but disable reconciliation
    // First, ensure we have a company (the trait might have created one)
    $company = Company::first();
    if (! $company) {
        // If no company exists, create one
        $currency = Currency::firstOrCreate(['code' => 'IQD'], [
            'name' => 'Iraqi Dinar',
            'symbol' => 'IQD',
            'is_active' => true,
            'decimal_places' => 3,
        ]);

        $company = Company::create([
            'name' => 'Test Company',
            'address' => 'Test Address',
            'tax_id' => 'TEST123',
            'currency_id' => $currency->id,
            'fiscal_country' => 'IQ',
            'enable_reconciliation' => false,
        ]);
    } else {
        // Disable reconciliation on the existing company
        $company->update(['enable_reconciliation' => false]);
    }

    // Create accounts that belong to this company and allow reconciliation
    // (we want to test global reconciliation, not account-level reconciliation)
    $account1 = Account::create([
        'company_id' => $company->id,
        'currency_id' => $company->currency_id,
        'code' => 'TEST1',
        'name' => 'Test Account 1',
        'type' => 'current_assets',
        'is_deprecated' => false,
        'allow_reconciliation' => true, // Account allows reconciliation
    ]);

    $account2 = Account::create([
        'company_id' => $company->id,
        'currency_id' => $company->currency_id,
        'code' => 'TEST2',
        'name' => 'Test Account 2',
        'type' => 'current_liabilities',
        'is_deprecated' => false,
        'allow_reconciliation' => true, // Account allows reconciliation
    ]);

    // Create journal and journal entry
    $journal = Journal::create([
        'company_id' => $company->id,
        'name' => 'Test Journal',
        'type' => 'miscellaneous',
        'short_code' => 'TST',
        'currency_id' => $company->currency_id,
        'default_debit_account_id' => $account1->id,
        'default_credit_account_id' => $account2->id,
    ]);

    // Create a user for the journal entry
    $testUser = User::factory()->create();

    $journalEntry = JournalEntry::create([
        'company_id' => $company->id,
        'journal_id' => $journal->id,
        'currency_id' => $company->currency_id,
        'entry_date' => now(),
        'reference' => 'TEST-001',
        'description' => 'Test entry',
        'created_by_user_id' => $testUser->id,
        'is_posted' => true,
        'total_debit' => Money::of(100, $company->currency->code),
        'total_credit' => Money::of(100, $company->currency->code),
    ]);

    // Create journal entry lines manually to ensure they belong to the correct company
    // First, let's verify the journal entry is correctly associated
    expect($journalEntry->company_id)->toBe($company->id);

    $line1 = new JournalEntryLine();
    $line1->journal_entry_id = $journalEntry->id;
    $line1->company_id = $company->id;
    $line1->account_id = $account1->id;
    $line1->debit = Money::of(100, $company->currency->code);
    $line1->credit = Money::of(0, $company->currency->code);
    $line1->description = 'Test debit line';
    $line1->save();

    $line2 = new JournalEntryLine();
    $line2->journal_entry_id = $journalEntry->id;
    $line2->company_id = $company->id;
    $line2->account_id = $account2->id;
    $line2->debit = Money::of(0, $company->currency->code);
    $line2->credit = Money::of(100, $company->currency->code);
    $line2->description = 'Test credit line';
    $line2->save();

    expect(fn() => $this->action->execute([$line1->id, $line2->id]))
        ->toThrow(ReconciliationDisabledException::class);
});

test('it throws exception when account does not allow reconciliation', function () {
    // Use the existing company and enable reconciliation
    $company = Company::first();
    if (! $company) {
        $company = Company::create([
            'name' => 'Test Company',
            'address' => 'Test Address',
            'tax_id' => '123456789',
            'currency_id' => 1,
            'fiscal_country' => 'IQ',
            'enable_reconciliation' => true,
        ]);
    } else {
        $company->update(['enable_reconciliation' => true]);
    }
    // Create account that doesn't allow reconciliation manually
    $account = Account::create([
        'company_id' => $company->id,
        'currency_id' => $company->currency_id,
        'code' => '1000',
        'name' => 'Test Account',
        'type' => 'current_assets',
        'is_deprecated' => false,
        'allow_reconciliation' => false,
    ]);

    // Create journal manually
    $journal = Journal::create([
        'company_id' => $company->id,
        'name' => 'Test Journal',
        'type' => 'miscellaneous',
        'short_code' => 'TST',
        'currency_id' => $company->currency_id,
        'default_debit_account_id' => $account->id,
        'default_credit_account_id' => $account->id,
    ]);

    // Create journal entry manually
    $journalEntry = JournalEntry::create([
        'company_id' => $company->id,
        'journal_id' => $journal->id,
        'currency_id' => $company->currency_id,
        'entry_date' => now(),
        'reference' => 'TEST-001',
        'description' => 'Test entry',
        'source_type' => 'test',
        'source_id' => 1,
        'created_by_user_id' => 1, // Assuming user with ID 1 exists
        'total_debit' => 100000, // Money in minor units
        'total_credit' => 100000, // Money in minor units
        'is_posted' => true,
    ]);

    // Create journal entry lines manually
    $line1 = JournalEntryLine::create([
        'company_id' => $company->id,
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $account->id,
        'partner_id' => null,
        'description' => 'Test line 1',
        'debit' => 100000, // Money in minor units
        'credit' => 0,
    ]);

    $line2 = JournalEntryLine::create([
        'company_id' => $company->id,
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $account->id,
        'partner_id' => null,
        'description' => 'Test line 2',
        'debit' => 0,
        'credit' => 100000, // Money in minor units
    ]);

    $lines = collect([$line1, $line2]);

    expect(fn() => $this->action->execute($lines->pluck('id')->toArray()))
        ->toThrow(AccountNotReconcilableException::class);
});

test('it throws exception when journal entry lines are unbalanced', function () {
    // Use existing company and enable reconciliation
    $company = Company::first();
    $company->update(['enable_reconciliation' => true]);

    // Create account that allows reconciliation
    $account = Account::create([
        'company_id' => $company->id,
        'currency_id' => $company->currency_id,
        'code' => '1001',
        'name' => 'Test Account Reconcilable',
        'type' => 'current_assets',
        'is_deprecated' => false,
        'allow_reconciliation' => true,
    ]);

    // Create journal and journal entry
    $journal = Journal::first() ?: Journal::create([
        'company_id' => $company->id,
        'name' => 'Test Journal',
        'type' => 'miscellaneous',
        'short_code' => 'TST',
        'currency_id' => $company->currency_id,
        'default_debit_account_id' => $account->id,
        'default_credit_account_id' => $account->id,
    ]);

    $journalEntry = JournalEntry::create([
        'company_id' => $company->id,
        'journal_id' => $journal->id,
        'currency_id' => $company->currency_id,
        'entry_date' => now(),
        'reference' => 'TEST-002',
        'description' => 'Test unbalanced entry',
        'source_type' => 'test',
        'source_id' => 1,
        'created_by_user_id' => 1,
        'total_debit' => 100000,
        'total_credit' => 50000,
        'is_posted' => true,
    ]);

    // Create unbalanced lines (100 debit, 50 credit)
    $line1 = JournalEntryLine::create([
        'company_id' => $company->id,
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $account->id,
        'description' => 'Debit line',
        'debit' => 100000,
        'credit' => 0,
    ]);

    $line2 = JournalEntryLine::create([
        'company_id' => $company->id,
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $account->id,
        'description' => 'Credit line',
        'debit' => 0,
        'credit' => 50000,
    ]);

    $lines = collect([$line1, $line2]);

    expect(fn() => $this->action->execute($lines->pluck('id')->toArray()))
        ->toThrow(UnbalancedReconciliationException::class);
});

test('it throws exception when journal entry lines have different partners for AR/AP reconciliation', function () {
    // Use existing company and enable reconciliation
    $company = Company::first();
    $company->update(['enable_reconciliation' => true]);

    // Create account that allows reconciliation
    $account = Account::create([
        'company_id' => $company->id,
        'currency_id' => $company->currency_id,
        'code' => '1002',
        'name' => 'Test Account AR',
        'type' => 'receivable',
        'is_deprecated' => false,
        'allow_reconciliation' => true,
    ]);

    // Create different partners
    $partner1 = Partner::create([
        'company_id' => $company->id,
        'name' => 'Partner 1',
        'email' => 'partner1@test.com',
        'type' => 'customer',
        'is_customer' => true,
        'is_vendor' => false,
    ]);

    $partner2 = Partner::create([
        'company_id' => $company->id,
        'name' => 'Partner 2',
        'email' => 'partner2@test.com',
        'type' => 'customer',
        'is_customer' => true,
        'is_vendor' => false,
    ]);

    // Create journal and journal entry
    $journal = Journal::first();
    $journalEntry = JournalEntry::create([
        'company_id' => $company->id,
        'journal_id' => $journal->id,
        'currency_id' => $company->currency_id,
        'entry_date' => now(),
        'reference' => 'TEST-003',
        'description' => 'Test partner mismatch entry',
        'source_type' => 'test',
        'source_id' => 1,
        'created_by_user_id' => 1,
        'total_debit' => 100000,
        'total_credit' => 100000,
        'is_posted' => true,
    ]);

    // Create balanced lines with different partners
    $line1 = JournalEntryLine::create([
        'company_id' => $company->id,
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $account->id,
        'partner_id' => $partner1->id,
        'description' => 'Line for partner 1',
        'debit' => 100000,
        'credit' => 0,
    ]);

    $line2 = JournalEntryLine::create([
        'company_id' => $company->id,
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $account->id,
        'partner_id' => $partner2->id,
        'description' => 'Line for partner 2',
        'debit' => 0,
        'credit' => 100000,
    ]);

    $lines = collect([$line1, $line2]);

    expect(fn() => $this->action->execute($lines->pluck('id')->toArray(), ReconciliationType::ManualArAp))
        ->toThrow(PartnerMismatchException::class);
});

test('it throws exception when journal entry lines are already reconciled', function () {
    // Use existing company and enable reconciliation
    $company = Company::first();
    $company->update(['enable_reconciliation' => true]);

    // Create account that allows reconciliation
    $account = Account::create([
        'company_id' => $company->id,
        'currency_id' => $company->currency_id,
        'code' => '1003',
        'name' => 'Test Account Already Reconciled',
        'type' => 'current_assets',
        'is_deprecated' => false,
        'allow_reconciliation' => true,
    ]);

    // Create journal and journal entry
    $journal = Journal::first();
    $journalEntry = JournalEntry::create([
        'company_id' => $company->id,
        'journal_id' => $journal->id,
        'currency_id' => $company->currency_id,
        'entry_date' => now(),
        'reference' => 'TEST-004',
        'description' => 'Test already reconciled entry',
        'source_type' => 'test',
        'source_id' => 1,
        'created_by_user_id' => 1,
        'total_debit' => 100000,
        'total_credit' => 100000,
        'is_posted' => true,
    ]);

    // Create balanced lines
    $line1 = JournalEntryLine::create([
        'company_id' => $company->id,
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $account->id,
        'description' => 'Already reconciled line 1',
        'debit' => 100000,
        'credit' => 0,
    ]);

    $line2 = JournalEntryLine::create([
        'company_id' => $company->id,
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $account->id,
        'description' => 'Already reconciled line 2',
        'debit' => 0,
        'credit' => 100000,
    ]);

    $lines = collect([$line1, $line2]);

    // Create existing reconciliation
    $existingReconciliation = Reconciliation::create([
        'company_id' => $company->id,
        'reconciliation_type' => ReconciliationType::ManualGeneral,
        'reconciled_by_user_id' => 1,
        'reconciled_at' => now(),
        'reference' => 'EXISTING-001',
        'description' => 'Existing reconciliation',
    ]);
    $existingReconciliation->journalEntryLines()->attach($lines->first()->id);

    expect(fn() => $this->action->execute($lines->pluck('id')->toArray()))
        ->toThrow(AlreadyReconciledException::class);
});

test('it successfully creates reconciliation for valid balanced lines', function () {
    // Use existing company and enable reconciliation
    $company = Company::first();
    $company->update(['enable_reconciliation' => true]);

    // Create account that allows reconciliation
    $account = Account::create([
        'company_id' => $company->id,
        'currency_id' => $company->currency_id,
        'code' => '1004',
        'name' => 'Test Account Success',
        'type' => 'receivable',
        'is_deprecated' => false,
        'allow_reconciliation' => true,
    ]);

    // Create partner
    $partner = Partner::create([
        'company_id' => $company->id,
        'name' => 'Success Partner',
        'email' => 'success@test.com',
        'type' => 'customer',
        'is_customer' => true,
        'is_vendor' => false,
    ]);

    // Create journal and journal entry
    $journal = Journal::first();
    $journalEntry = JournalEntry::create([
        'company_id' => $company->id,
        'journal_id' => $journal->id,
        'currency_id' => $company->currency_id,
        'entry_date' => now(),
        'reference' => 'TEST-005',
        'description' => 'Test successful reconciliation entry',
        'source_type' => 'test',
        'source_id' => 1,
        'created_by_user_id' => 1,
        'total_debit' => 100000,
        'total_credit' => 100000,
        'is_posted' => true,
    ]);

    // Create balanced lines with same partner
    $line1 = JournalEntryLine::create([
        'company_id' => $company->id,
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $account->id,
        'partner_id' => $partner->id,
        'description' => 'Success line 1',
        'debit' => 100000,
        'credit' => 0,
    ]);

    $line2 = JournalEntryLine::create([
        'company_id' => $company->id,
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $account->id,
        'partner_id' => $partner->id,
        'description' => 'Success line 2',
        'debit' => 0,
        'credit' => 100000,
    ]);

    $lines = collect([$line1, $line2]);

    $reconciliation = $this->action->execute(
        journalLineIds: $lines->pluck('id')->toArray(),
        reconciliationType: ReconciliationType::ManualArAp,
        reference: 'TEST-SUCCESS',
        description: 'Test reconciliation'
    );

    expect($reconciliation)
        ->toBeInstanceOf(Reconciliation::class)
        ->and($reconciliation->company_id)->toBe($company->id)
        ->and($reconciliation->reconciliation_type)->toBe(ReconciliationType::ManualArAp)
        ->and($reconciliation->reference)->toBe('TEST-SUCCESS')
        ->and($reconciliation->description)->toBe('Test reconciliation')
        ->and($reconciliation->journalEntryLines)->toHaveCount(2)
        ->and($reconciliation->isBalanced())->toBeTrue();
});

test('it allows reconciliation of lines without partners for general reconciliation', function () {
    // Use existing company and enable reconciliation
    $company = Company::first();
    $company->update(['enable_reconciliation' => true]);

    // Create account that allows reconciliation
    $account = Account::create([
        'company_id' => $company->id,
        'currency_id' => $company->currency_id,
        'code' => '1005',
        'name' => 'Test Account General',
        'type' => 'current_assets',
        'is_deprecated' => false,
        'allow_reconciliation' => true,
    ]);

    // Create journal and journal entry
    $journal = Journal::first();
    $journalEntry = JournalEntry::create([
        'company_id' => $company->id,
        'journal_id' => $journal->id,
        'currency_id' => $company->currency_id,
        'entry_date' => now(),
        'reference' => 'TEST-006',
        'description' => 'Test general reconciliation entry',
        'source_type' => 'test',
        'source_id' => 1,
        'created_by_user_id' => 1,
        'total_debit' => 100000,
        'total_credit' => 100000,
        'is_posted' => true,
    ]);

    // Create balanced lines without partners
    $line1 = JournalEntryLine::create([
        'company_id' => $company->id,
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $account->id,
        'partner_id' => null,
        'description' => 'General line 1',
        'debit' => 100000,
        'credit' => 0,
    ]);

    $line2 = JournalEntryLine::create([
        'company_id' => $company->id,
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $account->id,
        'partner_id' => null,
        'description' => 'General line 2',
        'debit' => 0,
        'credit' => 100000,
    ]);

    $lines = collect([$line1, $line2]);

    $reconciliation = $this->action->execute(
        journalLineIds: $lines->pluck('id')->toArray(),
        reconciliationType: ReconciliationType::ManualGeneral
    );

    expect($reconciliation)
        ->toBeInstanceOf(Reconciliation::class)
        ->and($reconciliation->reconciliation_type)->toBe(ReconciliationType::ManualGeneral)
        ->and($reconciliation->journalEntryLines)->toHaveCount(2);
});
