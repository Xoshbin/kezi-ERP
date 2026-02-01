<?php

declare(strict_types=1);

namespace Kezi\Accounting\Actions\Accounting;

use Illuminate\Support\Facades\DB;
use Kezi\Accounting\Enums\Accounting\FiscalPeriodState;
use Kezi\Accounting\Enums\Accounting\JournalEntryState;
use Kezi\Accounting\Events\FiscalPeriodClosed;
use Kezi\Accounting\Exceptions\FiscalPeriodNotReadyToCloseException;
use Kezi\Accounting\Models\FiscalPeriod;
use Kezi\Accounting\Models\JournalEntry;

/**
 * Closes a fiscal period and triggers lock date updates.
 *
 * This action validates that a period is ready to be closed (no draft entries,
 * parent year is open) before transitioning it to the Closed state and
 * dispatching the FiscalPeriodClosed event.
 */
final class CloseFiscalPeriodAction
{
    /**
     * Execute the action to close a fiscal period.
     *
     * @throws FiscalPeriodNotReadyToCloseException When validation fails
     */
    public function execute(FiscalPeriod $fiscalPeriod): FiscalPeriod
    {
        return DB::transaction(function () use ($fiscalPeriod): FiscalPeriod {
            $this->ensureCanClose($fiscalPeriod);

            $fiscalPeriod->update([
                'state' => FiscalPeriodState::Closed,
            ]);

            FiscalPeriodClosed::dispatch($fiscalPeriod);

            return $fiscalPeriod->refresh();
        });
    }

    /**
     * Ensure the fiscal period meets all requirements for closing.
     *
     * @throws FiscalPeriodNotReadyToCloseException
     */
    private function ensureCanClose(FiscalPeriod $fiscalPeriod): void
    {
        if (! $fiscalPeriod->canClose()) {
            throw new FiscalPeriodNotReadyToCloseException(
                __('accounting::fiscal_period.validation.not_open')
            );
        }

        if ($fiscalPeriod->fiscalYear->isClosed()) {
            throw new FiscalPeriodNotReadyToCloseException(
                __('accounting::fiscal_period.validation.year_closed')
            );
        }

        $this->ensureNoDraftEntries($fiscalPeriod);
    }

    /**
     * Verify no draft journal entries exist within the period.
     *
     * @throws FiscalPeriodNotReadyToCloseException
     */
    private function ensureNoDraftEntries(FiscalPeriod $fiscalPeriod): void
    {
        $draftCount = JournalEntry::query()
            ->where('company_id', $fiscalPeriod->fiscalYear->company_id)
            ->whereBetween('entry_date', [
                $fiscalPeriod->start_date,
                $fiscalPeriod->end_date,
            ])
            ->where('state', JournalEntryState::Draft)
            ->count();

        if ($draftCount > 0) {
            throw new FiscalPeriodNotReadyToCloseException(
                __('accounting::fiscal_period.validation.draft_entries', ['count' => $draftCount])
            );
        }
    }
}
