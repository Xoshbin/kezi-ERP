<?php

namespace Kezi\HR\Actions\HumanResources;

use App\Models\User;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Kezi\Accounting\Actions\Accounting\CreateJournalEntryAction;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Kezi\Accounting\Services\Accounting\LockDateService;
use Kezi\HR\Enums\CashAdvanceStatus;
use Kezi\HR\Models\CashAdvance;
use Kezi\Payment\Actions\Payments\CreatePaymentAction;
use Kezi\Payment\DataTransferObjects\Payments\CreatePaymentDTO;

class DisburseCashAdvanceAction
{
    public function __construct(
        private readonly CreateJournalEntryAction $createJournalEntryAction,
        private readonly CreatePaymentAction $createPaymentAction,
        private readonly LockDateService $lockDateService,
    ) {}

    public function execute(CashAdvance $cashAdvance, int $bankAccountId, User $user): void
    {
        DB::transaction(function () use ($cashAdvance, $bankAccountId, $user) {
            if ($cashAdvance->status !== CashAdvanceStatus::Approved) {
                throw new \InvalidArgumentException('Only approved cash advances can be disbursed.');
            }

            $company = $cashAdvance->company;
            $company->refresh();

            // Check lock date
            $this->lockDateService->enforce($company, Carbon::now());

            // Get employee advance receivable account
            $receivableAccountId = $company->default_employee_advance_receivable_account_id;
            if (! $receivableAccountId) {
                throw new \RuntimeException('Employee advance receivable account not configured for company.');
            }

            // Get default cash journal
            $journalId = null;
            if (! $journalId) {
                $cashJournal = \Kezi\Accounting\Models\Journal::where('company_id', $company->id)
                    ->whereIn('type', [
                        \Kezi\Accounting\Enums\Accounting\JournalType::Cash,
                        \Kezi\Accounting\Enums\Accounting\JournalType::Bank,
                    ])
                    ->first();

                if (! $cashJournal) {
                    throw new \RuntimeException('No cash or bank journal found for company.');
                }

                $journalId = $cashJournal->id;
            }

            $amount = $cashAdvance->approved_amount;

            // Create journal entry: Dr Employee Advance Receivable / Cr Bank
            $lines = [
                new CreateJournalEntryLineDTO(
                    account_id: $receivableAccountId,
                    debit: $amount,
                    credit: Money::zero($cashAdvance->currency->code),
                    description: "Cash advance disbursement: {$cashAdvance->employee->full_name}",
                    partner_id: null,
                    analytic_account_id: null,
                ),
                new CreateJournalEntryLineDTO(
                    account_id: $bankAccountId,
                    debit: Money::zero($cashAdvance->currency->code),
                    credit: $amount,
                    description: "Cash advance disbursement: {$cashAdvance->employee->full_name}",
                    partner_id: null,
                    analytic_account_id: null,
                ),
            ];

            $dto = new CreateJournalEntryDTO(
                company_id: $cashAdvance->company_id,
                journal_id: $journalId,
                currency_id: $cashAdvance->currency_id,
                entry_date: Carbon::now(),
                reference: $cashAdvance->advance_number,
                description: "Cash Advance Disbursement: {$cashAdvance->purpose}",
                created_by_user_id: $user->id,
                is_posted: true,
                lines: $lines,
                source_type: CashAdvance::class,
                source_id: $cashAdvance->id,
            );

            $journalEntry = $this->createJournalEntryAction->execute($dto);

            // Ensure we have a partner for the employee
            $partner = \Kezi\Foundation\Models\Partner::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'email' => $cashAdvance->employee->email,
                ],
                [
                    'name' => $cashAdvance->employee->full_name,
                    'type' => \Kezi\Foundation\Enums\Partners\PartnerType::Vendor,
                ]
            );

            // Create payment record
            $paymentDTO = new CreatePaymentDTO(
                company_id: $cashAdvance->company_id,
                journal_id: $journalId,
                currency_id: $cashAdvance->currency_id,
                payment_date: Carbon::now()->toDateString(),
                payment_type: \Kezi\Payment\Enums\Payments\PaymentType::Outbound,
                payment_method: \Kezi\Payment\Enums\Payments\PaymentMethod::BankTransfer,
                paid_to_from_partner_id: $partner->id,
                amount: $amount,
                document_links: [],
                reference: $cashAdvance->advance_number,
            );

            $payment = $this->createPaymentAction->execute($paymentDTO, $user);

            // Update cash advance
            $cashAdvance->update([
                'disbursed_amount' => $amount,
                'status' => CashAdvanceStatus::Disbursed,
                'disbursed_at' => now(),
                'disbursed_by_user_id' => $user->id,
                'disbursement_journal_entry_id' => $journalEntry->id,
                'payment_id' => $payment->id,
            ]);
        });
    }
}
