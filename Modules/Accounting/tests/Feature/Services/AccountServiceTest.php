<?php

namespace Modules\Accounting\Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\Accounting\Enums\Accounting\AccountType;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalEntryLine;
use Modules\Accounting\Services\AccountService;
use Modules\Foundation\Exceptions\DeletionNotAllowedException;
use Tests\TestCase;

class AccountServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AccountService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AccountService::class);
    }

    public function test_can_create_account_with_valid_data(): void
    {
        $company = \App\Models\Company::factory()->create();

        $data = [
            'code' => '1001',
            'name' => 'Cash',
            'type' => AccountType::CurrentAssets->value,
            'company_id' => $company->id,
        ];

        $account = $this->service->create($data);

        $this->assertInstanceOf(Account::class, $account);
        $this->assertEquals('Cash', $account->name);
        $this->assertDatabaseHas('accounts', [
            'code' => '1001',
            'type' => AccountType::CurrentAssets->value,
            'company_id' => $company->id,
        ]);
    }

    public function test_create_account_validation_fails_missing_fields(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->create([]);
    }

    public function test_create_account_validation_fails_duplicate_code_same_company(): void
    {
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

        $this->expectException(ValidationException::class);
        $this->service->create($data);
    }

    public function test_can_create_account_duplicate_code_different_company(): void
    {
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

        $this->assertInstanceOf(Account::class, $account);
        $this->assertDatabaseHas('accounts', [
            'code' => '1001',
            'company_id' => $company2->id,
        ]);
    }

    public function test_can_update_account_with_valid_data(): void
    {
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

        $this->assertEquals('1002', $updatedAccount->code);
        $this->assertEquals('Updated Name', $updatedAccount->name);
        $this->assertDatabaseHas('accounts', [
            'code' => '1002',
            'type' => AccountType::CurrentLiabilities->value,
            'company_id' => $account->company_id,
        ]);
    }

    public function test_update_account_validation_fails_duplicate_code_same_company(): void
    {
        $company = \App\Models\Company::factory()->create();
        $account1 = Account::factory()->create(['company_id' => $company->id, 'code' => '1001', 'type' => AccountType::CurrentAssets]);
        $account2 = Account::factory()->create(['company_id' => $company->id, 'code' => '1002', 'type' => AccountType::CurrentAssets]);

        $data = [
            'code' => '1001', // Duplicate of account1
            'name' => 'Updated Name',
            'type' => AccountType::CurrentLiabilities->value,
            'company_id' => $company->id,
        ];

        $this->expectException(ValidationException::class);
        $this->service->update($account2, $data);
    }

    public function test_can_update_account_ignoring_self_code(): void
    {
        $company = \App\Models\Company::factory()->create();
        $account = Account::factory()->create(['company_id' => $company->id, 'code' => '1001', 'type' => AccountType::CurrentAssets]);

        $data = [
            'code' => '1001', // Same code
            'name' => 'Updated Name',
            'type' => AccountType::CurrentLiabilities->value,
            'company_id' => $company->id,
        ];

        $updatedAccount = $this->service->update($account, $data);

        $this->assertEquals('Updated Name', $updatedAccount->name);
    }

    public function test_can_delete_account_no_journal_entries(): void
    {
        $account = Account::factory()->create(['type' => AccountType::CurrentAssets]);

        $this->service->delete($account);

        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
    }

    public function test_cannot_delete_account_with_journal_entries(): void
    {
        $account = Account::factory()->create(['type' => AccountType::CurrentAssets]);
        $journalEntry = JournalEntry::factory()->create(['company_id' => $account->company_id]);

        JournalEntryLine::factory()->create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $account->id,
        ]);

        $this->expectException(DeletionNotAllowedException::class);

        $this->service->delete($account);
    }
}
