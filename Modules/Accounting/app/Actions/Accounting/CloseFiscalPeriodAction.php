<?php

namespace Modules\Accounting\Actions\Accounting;

use Illuminate\Support\Facades\DB;
use Modules\Accounting\Enums\Accounting\FiscalPeriodState;
use Modules\Accounting\Enums\Accounting\JournalEntryState;
use Modules\Accounting\Events\FiscalPeriodClosed;
use Modules\Accounting\Exceptions\FiscalPeriodNotReadyToCloseException;
use Modules\Accounting\Models\FiscalPeriod;

class CloseFiscalPeriodAction
{
    /**
     * Close a fiscal period.
     *
     * @throws FiscalPeriodNotReadyToCloseException
     */
    public function execute(FiscalPeriod $fiscalPeriod): FiscalPeriod
    {
        return DB::transaction(function () use ($fiscalPeriod) {
            // Validate period can be closed
            $this->validateCanClose($fiscalPeriod);

            // Update state
            $fiscalPeriod->update([
                'state' => FiscalPeriodState::Closed,
            ]);

            // Dispatch event for lock date update
            event(new FiscalPeriodClosed($fiscalPeriod));

            return $fiscalPeriod->refresh();
        });
    }

    /**
     * Validate that the fiscal period can be closed.
     *
     * @throws FiscalPeriodNotReadyToCloseException
     */
    private function validateCanClose(FiscalPeriod $fiscalPeriod): void
    {
        // Check state
        if (! $fiscalPeriod->canClose()) {
            throw new FiscalPeriodNotReadyToCloseException(
                __('accounting::fiscal_period.validation.not_open')
            );
        }

        // Check parent fiscal year is not closed
        if ($fiscalPeriod->fiscalYear->isClosed()) {
            throw new FiscalPeriodNotReadyToCloseException(
                __('accounting::fiscal_period.validation.year_closed')
            );
        }

        // Check for draft journal entries in this period
        $draftEntries = DB::table('journal_entries')
            ->where('company_id', $fiscalPeriod->fiscalYear->company_id)
            ->whereBetween('entry_date', [$fiscalPeriod->start_date, $fiscalPeriod->end_date])
            ->where('state', JournalEntryState::Draft->value)
            ->count();

        if ($draftEntries > 0) {
            throw new FiscalPeriodNotReadyToCloseException(
                __('accounting::fiscal_period.validation.draft_entries', ['count' => $draftEntries])
            );
        }
    }
}
