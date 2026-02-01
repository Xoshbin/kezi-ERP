<?php

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Enums\Accounting\RootAccountType;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\AccountGroup;
use Jmeryar\Accounting\Services\AccountGroupService;

uses(RefreshDatabase::class);

describe('AccountGroup Model', function () {
    it('assigns account to correct group based on code', function () {
        $company = Company::factory()->create();

        $group = AccountGroup::factory()->create([
            'company_id' => $company->id,
            'code_prefix_start' => '1100',
            'code_prefix_end' => '119999',
            'level' => 1,
        ]);

        $account = Account::factory()->create([
            'company_id' => $company->id,
            'code' => '110101',
        ]);

        expect($account->fresh()->account_group_id)->toBe($group->id);
    });

    it('assigns to most specific group when nested', function () {
        $company = Company::factory()->create();

        $parentGroup = AccountGroup::factory()->create([
            'company_id' => $company->id,
            'code_prefix_start' => '11',
            'code_prefix_end' => '119999',
            'level' => 1,
        ]);

        $childGroup = AccountGroup::factory()->create([
            'company_id' => $company->id,
            'code_prefix_start' => '1101',
            'code_prefix_end' => '110199',
            'level' => 2,
            'parent_id' => $parentGroup->id,
        ]);

        $account = Account::factory()->create([
            'company_id' => $company->id,
            'code' => '110101',
        ]);

        expect($account->fresh()->account_group_id)->toBe($childGroup->id);
    });

    it('does not assign to group from different company', function () {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        $group = AccountGroup::factory()->create([
            'company_id' => $company1->id,
            'code_prefix_start' => '1100',
            'code_prefix_end' => '119999',
            'level' => 1,
        ]);

        $account = Account::factory()->create([
            'company_id' => $company2->id,
            'code' => '110101',
        ]);

        expect($account->fresh()->account_group_id)->toBeNull();
    });

    it('returns correct root type from code prefix', function () {
        $assetGroup = AccountGroup::factory()->make([
            'code_prefix_start' => '11',
            'code_prefix_end' => '1199',
        ]);
        expect($assetGroup->root_type)->toBe(RootAccountType::Asset);

        $liabilityGroup = AccountGroup::factory()->make([
            'code_prefix_start' => '21',
            'code_prefix_end' => '2199',
        ]);
        expect($liabilityGroup->root_type)->toBe(RootAccountType::Liability);

        $equityGroup = AccountGroup::factory()->make([
            'code_prefix_start' => '31',
            'code_prefix_end' => '3199',
        ]);
        expect($equityGroup->root_type)->toBe(RootAccountType::Equity);

        $incomeGroup = AccountGroup::factory()->make([
            'code_prefix_start' => '41',
            'code_prefix_end' => '4199',
        ]);
        expect($incomeGroup->root_type)->toBe(RootAccountType::Income);

        $expenseGroup = AccountGroup::factory()->make([
            'code_prefix_start' => '51',
            'code_prefix_end' => '5199',
        ]);
        expect($expenseGroup->root_type)->toBe(RootAccountType::Expense);
    });

    it('checks if code belongs to group range', function () {
        $group = AccountGroup::factory()->make([
            'code_prefix_start' => '1100',
            'code_prefix_end' => '1199',
        ]);

        expect($group->containsCode('1100'))->toBeTrue();
        expect($group->containsCode('1150'))->toBeTrue();
        expect($group->containsCode('1199'))->toBeTrue();
        expect($group->containsCode('1099'))->toBeFalse();
        expect($group->containsCode('1200'))->toBeFalse();
    });
});

describe('AccountGroupService', function () {
    it('validates no overlapping group ranges', function () {
        $company = Company::factory()->create();
        $service = app(AccountGroupService::class);

        AccountGroup::factory()->create([
            'company_id' => $company->id,
            'code_prefix_start' => '1100',
            'code_prefix_end' => '1199',
        ]);

        // Overlapping range
        $isValid = $service->validateNoOverlap(
            $company->id,
            '1150',
            '1250'
        );
        expect($isValid)->toBeFalse();

        // Non-overlapping range
        $isValid = $service->validateNoOverlap(
            $company->id,
            '1200',
            '1299'
        );
        expect($isValid)->toBeTrue();
    });

    it('calculates correct level for nested groups', function () {
        $company = Company::factory()->create();
        $service = app(AccountGroupService::class);

        // Root level group
        AccountGroup::factory()->create([
            'company_id' => $company->id,
            'code_prefix_start' => '1',
            'code_prefix_end' => '199999',
            'level' => 0,
        ]);

        // Calculate level for a child group
        $level = $service->calculateLevel($company->id, '11', '119999');
        expect($level)->toBe(1);

        // Create child and calculate grandchild level
        AccountGroup::factory()->create([
            'company_id' => $company->id,
            'code_prefix_start' => '11',
            'code_prefix_end' => '119999',
            'level' => 1,
        ]);

        $level = $service->calculateLevel($company->id, '1101', '110199');
        expect($level)->toBe(2);
    });

    it('reassigns all accounts to appropriate groups', function () {
        $company = Company::factory()->create();
        $service = app(AccountGroupService::class);

        // Create accounts first (no groups yet)
        $account1 = Account::factory()->create([
            'company_id' => $company->id,
            'code' => '110101',
        ]);
        $account2 = Account::factory()->create([
            'company_id' => $company->id,
            'code' => '210101',
        ]);

        expect($account1->account_group_id)->toBeNull();
        expect($account2->account_group_id)->toBeNull();

        // Now create groups
        $assetGroup = AccountGroup::factory()->create([
            'company_id' => $company->id,
            'code_prefix_start' => '11',
            'code_prefix_end' => '119999',
            'level' => 1,
        ]);
        $liabilityGroup = AccountGroup::factory()->create([
            'company_id' => $company->id,
            'code_prefix_start' => '21',
            'code_prefix_end' => '219999',
            'level' => 1,
        ]);

        // Reassign all accounts
        $count = $service->reassignAllAccounts($company->id);

        expect($count)->toBe(2);
        expect($account1->fresh()->account_group_id)->toBe($assetGroup->id);
        expect($account2->fresh()->account_group_id)->toBe($liabilityGroup->id);
    });
});

describe('Account Model - Group Integration', function () {
    it('has accountGroup relationship', function () {
        $company = Company::factory()->create();

        $group = AccountGroup::factory()->create([
            'company_id' => $company->id,
            'code_prefix_start' => '11',
            'code_prefix_end' => '119999',
        ]);

        $account = Account::factory()->create([
            'company_id' => $company->id,
            'code' => '110101',
        ]);

        expect($account->accountGroup)->not->toBeNull();
        expect($account->accountGroup->id)->toBe($group->id);
    });

    it('returns correct root type from account type', function () {
        $account = Account::factory()->asset()->make();
        expect($account->root_type)->toBe(RootAccountType::Asset);

        $account = Account::factory()->liability()->make();
        expect($account->root_type)->toBe(RootAccountType::Liability);

        $account = Account::factory()->equity()->make();
        expect($account->root_type)->toBe(RootAccountType::Equity);

        $account = Account::factory()->income()->make();
        expect($account->root_type)->toBe(RootAccountType::Income);

        $account = Account::factory()->expense()->make();
        expect($account->root_type)->toBe(RootAccountType::Expense);
    });

    it('isPostable always returns true for accounts', function () {
        $account = Account::factory()->make();
        expect($account->isPostable())->toBeTrue();
    });
});
