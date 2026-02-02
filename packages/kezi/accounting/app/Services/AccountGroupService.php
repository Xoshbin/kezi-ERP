<?php

namespace Kezi\Accounting\Services;

use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\AccountGroup;

/**
 * Service for managing account groups and auto-assignment of accounts.
 */
class AccountGroupService
{
    /**
     * Auto-assign account to the most specific matching group based on code.
     */
    public function assignAccountToGroup(Account $account): void
    {
        $group = AccountGroup::where('company_id', $account->company_id)
            ->where('code_prefix_start', '<=', $account->code)
            ->where('code_prefix_end', '>=', $account->code)
            ->orderBy('level', 'desc') // Most specific group first
            ->first();

        if ($group && $account->account_group_id !== $group->id) {
            $account->account_group_id = $group->id;
            $account->saveQuietly(); // Avoid observer recursion
        }
    }

    /**
     * Validate group code range doesn't overlap with existing groups at the same level.
     */
    public function validateNoOverlap(
        int $companyId,
        string $start,
        string $end,
        ?int $excludeId = null
    ): bool {
        $query = AccountGroup::where('company_id', $companyId)
            ->where(function ($q) use ($start, $end) {
                // Check if new range overlaps with existing ranges
                $q->where(function ($inner) use ($start, $end) {
                    $inner->where('code_prefix_start', '<=', $end)
                        ->where('code_prefix_end', '>=', $start);
                });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return ! $query->exists();
    }

    /**
     * Calculate the appropriate level for a new group.
     */
    public function calculateLevel(int $companyId, string $start, string $end): int
    {
        // Find parent group that fully contains this range
        $parent = AccountGroup::where('company_id', $companyId)
            ->where('code_prefix_start', '<=', $start)
            ->where('code_prefix_end', '>=', $end)
            ->orderBy('level', 'desc')
            ->first();

        return $parent ? $parent->level + 1 : 0;
    }

    /**
     * Reassign all accounts in company to their appropriate groups.
     * Useful after bulk group changes.
     */
    public function reassignAllAccounts(int $companyId): int
    {
        $accounts = Account::where('company_id', $companyId)->get();
        $count = 0;

        foreach ($accounts as $account) {
            $oldGroupId = $account->account_group_id;
            $this->assignAccountToGroup($account);
            if ($account->account_group_id !== $oldGroupId) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get the next available account code within a group's prefix range.
     * Returns null if no more codes are available.
     */
    public function getNextAccountCode(AccountGroup $group): ?string
    {
        $start = $group->code_prefix_start;
        $end = $group->code_prefix_end;
        $codeLength = strlen($start);

        // Find the highest existing code within this group's range
        $highestCode = Account::where('company_id', $group->company_id)
            ->where('code', '>=', $start)
            ->where('code', '<=', $end)
            ->orderByRaw('CAST(code AS UNSIGNED) DESC')
            ->value('code');

        if ($highestCode === null) {
            // No accounts exist in this range, return the start code
            return $start;
        }

        // Increment the highest code
        $nextCodeNum = ((int) $highestCode) + 1;
        $nextCode = str_pad((string) $nextCodeNum, $codeLength, '0', STR_PAD_LEFT);

        // Check if next code is still within range
        if ($nextCode > $end) {
            return null; // No more codes available
        }

        return $nextCode;
    }
}
