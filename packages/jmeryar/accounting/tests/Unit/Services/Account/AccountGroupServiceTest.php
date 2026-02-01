<?php

namespace Jmeryar\Accounting\tests\Unit\Services\Account;

use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\AccountGroup;
use Jmeryar\Accounting\Services\AccountGroupService;

beforeEach(function () {
    $this->service = new AccountGroupService;
    $this->company = \App\Models\Company::factory()->create();
});

test('assignAccountToGroup assigns account to the most specific matching group', function () {
    // Create a parent group (Level 0)
    $parentGroup = AccountGroup::factory()->create([
        'company_id' => $this->company->id,
        'code_prefix_start' => '1000',
        'code_prefix_end' => '1999',
        'level' => 0,
    ]);

    // Create a specific child group (Level 1)
    $childGroup = AccountGroup::factory()->create([
        'company_id' => $this->company->id,
        'code_prefix_start' => '1100',
        'code_prefix_end' => '1199',
        'level' => 1,
    ]);

    // Create an account that falls into both, but should match the child (more specific)
    $account = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '1150',
        'account_group_id' => null,
    ]);

    $this->service->assignAccountToGroup($account);

    expect($account->fresh()->account_group_id)->toBe($childGroup->id);
});

test('assignAccountToGroup assigns to parent if no specific child matches', function () {
    // Parent matches 1000-1999
    $parentGroup = AccountGroup::factory()->create([
        'company_id' => $this->company->id,
        'code_prefix_start' => '1000',
        'code_prefix_end' => '1999',
        'level' => 0,
    ]);

    // Child matches 1100-1199
    AccountGroup::factory()->create([
        'company_id' => $this->company->id,
        'code_prefix_start' => '1100',
        'code_prefix_end' => '1199',
        'level' => 1,
    ]);

    // Account is 1200 - matches parent only, not child
    $account = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '1200',
        'account_group_id' => null,
    ]);

    $this->service->assignAccountToGroup($account);

    expect($account->fresh()->account_group_id)->toBe($parentGroup->id);
});

test('validateNoOverlap detects exact overlap', function () {
    AccountGroup::factory()->create([
        'company_id' => $this->company->id,
        'code_prefix_start' => '100',
        'code_prefix_end' => '200',
    ]);

    // Check same range
    $isValid = $this->service->validateNoOverlap($this->company->id, '100', '200');
    expect($isValid)->toBeFalse();
});

test('validateNoOverlap detects partial overlap', function () {
    AccountGroup::factory()->create([
        'company_id' => $this->company->id,
        'code_prefix_start' => '100',
        'code_prefix_end' => '200',
    ]);

    // Check overlapping start
    $isValid1 = $this->service->validateNoOverlap($this->company->id, '050', '150');
    expect($isValid1)->toBeFalse();

    // Check overlapping end
    $isValid2 = $this->service->validateNoOverlap($this->company->id, '150', '250');
    expect($isValid2)->toBeFalse();

    // Check subset
    $isValid3 = $this->service->validateNoOverlap($this->company->id, '120', '180');
    expect($isValid3)->toBeFalse();

    // Check superset
    $isValid4 = $this->service->validateNoOverlap($this->company->id, '001', '300');
    expect($isValid4)->toBeFalse();
});

test('validateNoOverlap allows non overlapping ranges', function () {
    AccountGroup::factory()->create([
        'company_id' => $this->company->id,
        'code_prefix_start' => '100',
        'code_prefix_end' => '200',
    ]);

    // Distinct range before
    $isValid1 = $this->service->validateNoOverlap($this->company->id, '010', '099');
    expect($isValid1)->toBeTrue();

    // Distinct range after
    $isValid2 = $this->service->validateNoOverlap($this->company->id, '201', '300');
    expect($isValid2)->toBeTrue();
});

test('validateNoOverlap ignores self when excludeId is provided', function () {
    $group = AccountGroup::factory()->create([
        'company_id' => $this->company->id,
        'code_prefix_start' => '100',
        'code_prefix_end' => '200',
    ]);

    // Check same range but exclude this group (update scenario)
    $isValid = $this->service->validateNoOverlap($this->company->id, '100', '200', $group->id);
    expect($isValid)->toBeTrue();
});

test('calculateLevel determines correct level based on hierarchy', function () {
    // No groups initially
    $level = $this->service->calculateLevel($this->company->id, '100', '200');
    expect($level)->toBe(0);

    // Create a parent group (Level 0)
    AccountGroup::factory()->create([
        'company_id' => $this->company->id,
        'code_prefix_start' => '100',
        'code_prefix_end' => '500',
        'level' => 0,
    ]);

    // New child group within this parent should be Level 1
    $level = $this->service->calculateLevel($this->company->id, '150', '200');
    expect($level)->toBe(1);

    // Create that child group (Level 1)
    AccountGroup::factory()->create([
        'company_id' => $this->company->id,
        'code_prefix_start' => '150',
        'code_prefix_end' => '250',
        'level' => 1,
    ]);

    // Grandchild group within child should be Level 2
    $level = $this->service->calculateLevel($this->company->id, '160', '190');
    expect($level)->toBe(2);
});

test('reassignAllAccounts updates accounts to correct groups', function () {
    // Create accounts FIRST so they are initially unassigned (since groups don't exist yet)
    $acc1 = Account::factory()->create(['company_id' => $this->company->id, 'code' => '1050', 'account_group_id' => null]);
    $acc2 = Account::factory()->create(['company_id' => $this->company->id, 'code' => '2050', 'account_group_id' => null]);
    // Create an account that won't match any group
    $acc3 = Account::factory()->create(['company_id' => $this->company->id, 'code' => '3000', 'account_group_id' => null]);

    // Now create the groups
    $groupA = AccountGroup::factory()->create([
        'company_id' => $this->company->id,
        'code_prefix_start' => '1000',
        'code_prefix_end' => '1999',
    ]);

    $groupB = AccountGroup::factory()->create([
        'company_id' => $this->company->id,
        'code_prefix_start' => '2000',
        'code_prefix_end' => '2999',
    ]);

    $count = $this->service->reassignAllAccounts($this->company->id);

    expect($count)->toBe(2); // acc1 and acc2 should be updated
    expect($acc1->fresh()->account_group_id)->toBe($groupA->id);
    expect($acc2->fresh()->account_group_id)->toBe($groupB->id);
    expect($acc3->fresh()->account_group_id)->toBeNull();
});

test('getNextAccountCode returns start code if no accounts exist', function () {
    $group = AccountGroup::factory()->create([
        'company_id' => $this->company->id,
        'code_prefix_start' => '1000',
        'code_prefix_end' => '1999',
    ]);

    $code = $this->service->getNextAccountCode($group);

    expect($code)->toBe('1000');
});

test('getNextAccountCode increments highest existing code', function () {
    $group = AccountGroup::factory()->create([
        'company_id' => $this->company->id,
        'code_prefix_start' => '1000',
        'code_prefix_end' => '1999',
    ]);

    Account::factory()->create(['company_id' => $this->company->id, 'code' => '1005']);

    $code = $this->service->getNextAccountCode($group);

    expect($code)->toBe('1006');
});

test('getNextAccountCode returns null if range is full', function () {
    $group = AccountGroup::factory()->create([
        'company_id' => $this->company->id,
        'code_prefix_start' => '10',
        'code_prefix_end' => '11',
    ]);

    Account::factory()->create(['company_id' => $this->company->id, 'code' => '11']);

    $code = $this->service->getNextAccountCode($group);

    expect($code)->toBeNull();
});
