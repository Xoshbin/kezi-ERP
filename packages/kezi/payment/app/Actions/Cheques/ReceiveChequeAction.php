<?php

namespace Kezi\Payment\Actions\Cheques;

use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Kezi\Accounting\Services\Accounting\LockDateService;
use Kezi\Payment\DataTransferObjects\Cheques\CreateChequeDTO;
use Kezi\Payment\Enums\Cheques\ChequeStatus;
use Kezi\Payment\Enums\Cheques\ChequeType;
use Kezi\Payment\Models\Cheque;

class ReceiveChequeAction
{
    public function __construct(
        private readonly LockDateService $lockDateService,
    ) {}

    public function execute(CreateChequeDTO $dto, User $user): Cheque
    {
        $company = Company::findOrFail($dto->company_id);
        $this->lockDateService->enforce($company, Carbon::parse($dto->issue_date));

        if ($dto->type !== ChequeType::Receivable) {
            throw new \InvalidArgumentException('ReceiveChequeAction is only for receivable cheques.');
        }

        return DB::transaction(function () use ($dto, $company) {

            // Calculate Base Currency Amount
            $amountCompanyCurrency = $dto->amount;
            if ($dto->currency_id !== $company->currency_id) {
                $converter = app(\Kezi\Foundation\Services\CurrencyConverterService::class);
                $currency = \Kezi\Foundation\Models\Currency::findOrFail($dto->currency_id);
                $rate = $converter->getExchangeRate($currency, Carbon::parse($dto->issue_date), $company) ?? 1.0;
                $amountCompanyCurrency = $dto->amount->multipliedBy($rate, \Brick\Math\RoundingMode::HALF_UP);
            }

            return Cheque::create([
                'company_id' => $dto->company_id,
                'journal_id' => $dto->journal_id, // This would be the Journal we eventually deposit into (Bank)
                'partner_id' => $dto->partner_id,
                'currency_id' => $dto->currency_id,
                'payment_id' => $dto->payment_id,
                'cheque_number' => $dto->cheque_number,
                'amount' => $dto->amount,
                'amount_company_currency' => $amountCompanyCurrency,
                'issue_date' => $dto->issue_date,
                'due_date' => $dto->due_date,
                'type' => ChequeType::Receivable,
                'status' => ChequeStatus::Draft,
                'payee_name' => $dto->payee_name,
                'bank_name' => $dto->bank_name,
                'memo' => $dto->memo,
            ]);
        });
    }
}
