<?php

namespace Modules\Accounting\Services\Consolidation;

use App\Models\Company;
use Carbon\Carbon;
use Modules\Accounting\Models\JournalEntryLine;

class InterCompanyEliminationService
{
    /**
     * Identify inter-company balances that should be eliminated.
     *
     * @param  array<int>  $companyIds  Consolidation Scope IDs
     * @param  Carbon  $date  Reporting Date
     */
    public function identifyInterCompanyBalances(array $companyIds, Carbon $date): array
    {
        return JournalEntryLine::query()
            ->whereIn('company_id', $companyIds)
            // Ensure JE is posted and valid up to date
            ->whereHas('journalEntry', function ($query) use ($date) {
                $query->where('is_posted', true)
                    ->where('entry_date', '<=', $date);
            })
            // Account Type Check
            ->whereHas('account', function ($query) {
                $query->whereIn('type', [
                    \Modules\Accounting\Enums\Accounting\AccountType::Receivable,
                    \Modules\Accounting\Enums\Accounting\AccountType::Payable,
                ]);
            })
            // Partner Link Check
            ->whereHas('partner', function ($query) use ($companyIds) {
                $query->whereIn('linked_company_id', $companyIds);
            })
            ->with(['company', 'account', 'partner', 'journalEntry'])
            ->get()
            ->all();
    }
}
