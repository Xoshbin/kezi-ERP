<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\Accounting\Enums\Accounting\AccountType;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalEntryLine;
use Modules\Accounting\Services\AccountService;
use Modules\Foundation\Exceptions\DeletionNotAllowedException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(AccountService::class);
});

test('can create account with valid data', function () {
    $company = \App\Models\Company::factory()->create();

    $data = [
        'code' => '1001',
        'name' => 'Cash',
        'type' => AccountType::CurrentAssets->value,
        'company_id' => $company->id,
    ];

    $account = $this->service->create($data);

    expect($account)->toBeInstanceOf(Account::class);
    expect($account->name)->toBe('Cash');
    $this->assertDatabaseHas('accounts', [
        'code' => '1001',
        'type' => AccountType::CurrentAssets->value,
        'company_id' => $company->id,
    ]);
});

test('create account validation fails missing fields', function () {
    expect(fn () => $this->service->create([]))
        ->toThrow(ValidationException::class);
});

test('create account validation fails duplicate code same company', function () {
    $company = \App\Models\Company::factory()->create();
    Account::factory()->create([
        'company_id' => $company->id,
        'code' => '1001',
        'type' => AccountType::CurrentAssets,
    ]);

    $data = [
        'code' => '1001',
        'name' => 'Cash Duplicate',
        'type' => AccountType::CurrentAssets->value,
        'company_id' => $company->id,
    ];

    expect(fn () => $this->service->create($data))
        ->toThrow(ValidationException::class);
});

test('can create account duplicate code different company', function () {
    $company1 = \App\Models\Company::factory()->create();
    $company2 = \App\Models\Company::factory()->create();

    Account::factory()->create([
        'company_id' => $company1->id,
        'code' => '1001',
        'type' => AccountType::CurrentAssets,
    ]);

    $data = [
        'code' => '1001',
        'name' => 'Cash Company 2',
        'type' => AccountType::CurrentAssets->value,
        'company_id' => $company2->id,
    ];

    $account = $this->service->create($data);

    expect($account)->toBeInstanceOf(Account::class);
    $this->assertDatabaseHas('accounts', [
        'code' => '1001',
        'company_id' => $company2->id,
    ]);
});

test('can update account with valid data', function () {
    $account = Account::factory()->create([
            'type' => AccountType::CurrentAssets,
    ]);
    $newData = [
        'code' => '1002',
        'name' => 'Updated Name',
        'type' => AccountType::CurrentLiabilities->value,
        'company_id' => $account->company_id,
    ];

    $updatedAccount = $this->service->update($account, $newData);

    expect($updatedAccount->code)->toBe('1002');
    expect($updatedAccount->name)->toBe('Updated Name');
    $this->assertDatabaseHas('accounts', [
        'code' => '1002',
        'type' => AccountType::CurrentLiabilities->value,
        'company_id' => $account->company_id,
    ]);
});

test('update account validation fails duplicate code same company', function () {
    $company = \App\Models\Company::factory()->create();
    $account1 = Account::factory()->create(['company_id' => $company->id, 'code' => '1001', 'type' => AccountType::CurrentAssets]);
    $account2 = Account::factory()->create(['company_id' => $company->id, 'code' => '1002', 'type' => AccountType::CurrentAssets]);

    $data = [
        'code' => '1001', // Duplicate of account1
        'name' => 'Updated Name',
        'type' => AccountType::CurrentLiabilities->value,
        'company_id' => $company->id,
    ];

    expect(fn () => $this->service->update($account2, $data))
        ->toThrow(ValidationException::class);
});

test('can update account ignoring self code', function () {
    $company = \App\Models\Company::factory()->create();
    $account = Account::factory()->create(['company_id' => $company->id, 'code' => '1001', 'type' => AccountType::CurrentAssets]);

    $data = [
        'code' => '1001', // Same code
        'name' => 'Updated Name',
        'type' => AccountType::CurrentLiabilities->value,
        'company_id' => $company->id,
    ];

    $updatedAccount = $this->service->update($account, $data);

    expect($updatedAccount->name)->toBe('Updated Name');
});

test('can delete account no journal entries', function () {
    $account = Account::factory()->create(['type' => AccountType::CurrentAssets]);

    $this->service->delete($account);

    $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
});

test('cannot delete account with journal entries', function () {
    $account = Account::factory()->create(['type' => AccountType::CurrentAssets]);
    $journalEntry = JournalEntry::factory()->create(['company_id' => $account->company_id]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $account->id,
    ]);

    expect(fn () => $this->service->delete($account))
        ->toThrow(DeletionNotAllowedException::class);
});
