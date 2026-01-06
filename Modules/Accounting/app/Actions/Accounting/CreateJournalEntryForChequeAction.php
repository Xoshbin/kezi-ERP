<?php

namespace Modules\Accounting\Actions\Accounting;

use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalEntry;
use Modules\Payment\Enums\Cheques\ChequeType;
use Modules\Payment\Models\Cheque;

class CreateJournalEntryForChequeAction
{
    public function __construct(
        private readonly \Modules\Accounting\Actions\Accounting\CreateJournalEntryAction $createJournalEntryAction,
    ) {}

    public function execute(Cheque $cheque, string $context): ?JournalEntry
    {
        return DB::transaction(function () use ($cheque, $context) {
            $company = $cheque->company;
            $description = $this->getDescription($cheque, $context);

            // Define Lines based on Context and Type
            $linesData = match ($context) {
                'handover' => $this->getHandoverLines($cheque), // Payable only
                'deposit' => $this->getDepositLines($cheque),   // Receivable only
                'clear' => $this->getClearLines($cheque),       // Both
                default => throw new \InvalidArgumentException("Invalid context: $context"),
            };

            if (empty($linesData)) {
                return null;
            }

            // Convert array lines to DTOs
            $lineDTOs = [];
            foreach ($linesData as $line) {
                $lineDTOs[] = new \Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO(
                    account_id: $line['account_id'],
                    debit: $line['debit'],
                    credit: $line['credit'],
                    description: $description, // Use main description or specific logic?
                    partner_id: $line['partner_id'] ?? null,
                    analytic_account_id: null,
                );
            }

            $dto = new \Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO(
                company_id: $cheque->company_id,
                journal_id: $cheque->journal_id,
                currency_id: $cheque->currency_id, // Transaction currency
                entry_date: now()->format('Y-m-d'), // Use current date for the event
                reference: $cheque->cheque_number,
                description: $description,
                created_by_user_id: auth()->id() ?? $cheque->partner_id, // User ID is explicitly needed?
                is_posted: true, // Cheque movements are posted immediately? Yes usually.
                lines: $lineDTOs,
                source_type: Cheque::class,
                source_id: $cheque->id,
            );

            // Execute Action
            return $this->createJournalEntryAction->execute($dto);
        });
    }

    private function getDescription(Cheque $cheque, string $context): string
    {
        $prefix = match ($context) {
            'handover' => 'Cheque Issued',
            'deposit' => 'Cheque Deposited',
            'clear' => 'Cheque Cleared',
            default => 'Cheque',
        };

        return "$prefix: {$cheque->cheque_number} - {$cheque->payee_name}";
    }

    private function getHandoverLines(Cheque $cheque): array
    {
        if ($cheque->type !== ChequeType::Payable) {
            throw new \InvalidArgumentException('Handover is only for Payable cheques.');
        }

        $company = $cheque->company;
        $payableAccount = $company->defaultPdcPayableAccount;

        if (! $payableAccount) {
            throw new \RuntimeException('Default PDC Payable Account not configured.');
        }

        // Dr. AP (Partner)
        // Cr. Outstanding Cheques Payable

        return [
            [
                'account_id' => $cheque->partner->receivable_account_id ?? $company->default_accounts_payable_id, // Fallback? Check partner type. Vendor = Payable.
                'partner_id' => $cheque->partner_id,
                'debit' => $cheque->amount_company_currency,
                'credit' => Money::zero($cheque->currency->code),
            ],
            [
                'account_id' => $payableAccount->id,
                'partner_id' => $cheque->partner_id, // Optional track partner on liability
                'debit' => Money::zero($cheque->currency->code),
                'credit' => $cheque->amount_company_currency,
            ],
        ];
    }

    private function getDepositLines(Cheque $cheque): array
    {
        if ($cheque->type !== ChequeType::Receivable) {
            throw new \InvalidArgumentException('Deposit is only for Receivable cheques.');
        }

        $company = $cheque->company;
        $pdcReceivableAccount = $company->defaultPdcReceivableAccount; // "Cheques in Collection"

        if (! $pdcReceivableAccount) {
            throw new \RuntimeException('Default PDC Receivable Account not configured.');
        }

        // Dr. Cheques in Collection
        // Cr. AR (Partner)

        return [
            [
                'account_id' => $pdcReceivableAccount->id,
                'partner_id' => $cheque->partner_id,
                'debit' => $cheque->amount_company_currency,
                'credit' => Money::zero($cheque->currency->code),
            ],
            [
                'account_id' => $cheque->partner->receivable_account_id ?? $company->default_accounts_receivable_id,
                'partner_id' => $cheque->partner_id,
                'debit' => Money::zero($cheque->currency->code),
                'credit' => $cheque->amount_company_currency,
            ],
        ];
    }

    private function getClearLines(Cheque $cheque): array
    {
        $company = $cheque->company;
        $bankAccount = $cheque->journal->default_account_id; // The specific bank account

        if (! $bankAccount) {
            throw new \RuntimeException('Journal default account not configured.');
        }

        if ($cheque->type === ChequeType::Payable) {

            $payableAccount = $company->defaultPdcPayableAccount;
            if (! $payableAccount) {
                throw new \RuntimeException('Default PDC Payable Account not configured.');
            }

            // Dr. Outstanding Cheques Payable
            // Cr. Bank Account

            return [
                [
                    'account_id' => $payableAccount->id,
                    'partner_id' => $cheque->partner_id,
                    'debit' => $cheque->amount_company_currency,
                    'credit' => Money::zero($cheque->currency->code),
                ],
                [
                    'account_id' => $bankAccount,
                    'partner_id' => null,
                    'debit' => Money::zero($cheque->currency->code),
                    'credit' => $cheque->amount_company_currency,
                ],
            ];

        } else {
            // Receivable
            // Dr. Bank Account
            // Cr. Cheques in Collection (PDC Receivable)

            $pdcReceivableAccount = $company->defaultPdcReceivableAccount;
            if (! $pdcReceivableAccount) {
                throw new \RuntimeException('Default PDC Receivable Account not configured.');
            }

            return [
                [
                    'account_id' => $bankAccount,
                    'partner_id' => null,
                    'debit' => $cheque->amount_company_currency,
                    'credit' => Money::zero($cheque->currency->code),
                ],
                [
                    'account_id' => $pdcReceivableAccount->id,
                    'partner_id' => $cheque->partner_id,
                    'debit' => Money::zero($cheque->currency->code),
                    'credit' => $cheque->amount_company_currency,
                ],
            ];
        }
    }
}
