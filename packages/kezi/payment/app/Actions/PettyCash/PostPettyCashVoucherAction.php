<?php

namespace Kezi\Payment\Actions\PettyCash;

use App\Models\User;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Kezi\Accounting\Actions\Accounting\CreateJournalEntryAction;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Accounting\Services\Accounting\LockDateService;
use Kezi\Payment\Enums\PettyCash\PettyCashFundStatus;
use Kezi\Payment\Enums\PettyCash\PettyCashVoucherStatus;
use Kezi\Payment\Models\PettyCash\PettyCashVoucher;

class PostPettyCashVoucherAction
{
    public function __construct(
        private readonly CreateJournalEntryAction $createJournalEntryAction,
        private readonly LockDateService $lockDateService,
    ) {}

    public function execute(PettyCashVoucher $voucher, User $user): JournalEntry
    {
        return DB::transaction(function () use ($voucher, $user) {
            // Validate fund is active
            if ($voucher->fund->status !== PettyCashFundStatus::Active) {
                throw new \InvalidArgumentException('Cannot post voucher for a closed fund.');
            }

            // Validate sufficient funds
            if ($voucher->fund->current_balance->isLessThan($voucher->amount)) {
                throw new \InvalidArgumentException('Insufficient petty cash balance.');
            }

            // Check lock date
            $company = $voucher->company;
            $company->refresh(); // Ensure we have the latest data from DB
            $this->lockDateService->enforce($company, Carbon::parse($voucher->voucher_date));

            // Get or create default cash journal
            $journalId = $company->default_cash_journal_id;

            if (! $journalId) {
                // Fallback: use first cash journal for company
                $cashJournal = \Kezi\Accounting\Models\Journal::where('company_id', $company->id)
                    ->where('type', 'cash')
                    ->first();

                if (! $cashJournal) {
                    throw new \RuntimeException('No cash journal found for company. Please configure a default cash journal.');
                }

                $journalId = $cashJournal->id;
            }

            // Create journal entry: Dr Expense / Cr Petty Cash
            $lines = [
                new CreateJournalEntryLineDTO(
                    account_id: $voucher->expense_account_id,
                    debit: $voucher->amount,
                    credit: Money::zero($voucher->fund->currency->code),
                    description: $voucher->description,
                    partner_id: $voucher->partner_id,
                    analytic_account_id: null,
                ),
                new CreateJournalEntryLineDTO(
                    account_id: $voucher->fund->account_id,
                    debit: Money::zero($voucher->fund->currency->code),
                    credit: $voucher->amount,
                    description: $voucher->description,
                    partner_id: $voucher->partner_id,
                    analytic_account_id: null,
                ),
            ];

            $dto = new CreateJournalEntryDTO(
                company_id: $voucher->company_id,
                journal_id: $journalId,
                currency_id: $voucher->fund->currency_id,
                entry_date: $voucher->voucher_date,
                reference: $voucher->voucher_number,
                description: "Petty Cash: {$voucher->description}",
                created_by_user_id: $user->id,
                is_posted: true,
                lines: $lines,
                source_type: PettyCashVoucher::class,
                source_id: $voucher->id,
            );

            $journalEntry = $this->createJournalEntryAction->execute($dto);

            // Update voucher and fund balance
            $voucher->update([
                'status' => PettyCashVoucherStatus::Posted,
                'journal_entry_id' => $journalEntry->id,
            ]);

            $voucher->fund->update([
                'current_balance' => $voucher->fund->current_balance->minus($voucher->amount),
            ]);

            return $journalEntry;
        });
    }
}
