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
use Kezi\Payment\Models\Chequebook;

class IssueChequeAction
{
    public function __construct(
        private readonly LockDateService $lockDateService,
    ) {}

    public function execute(CreateChequeDTO $dto, User $user): Cheque
    {
        $company = Company::findOrFail($dto->company_id);
        $this->lockDateService->enforce($company, Carbon::parse($dto->issue_date));

        if ($dto->type !== ChequeType::Payable) {
            throw new \InvalidArgumentException('IssueChequeAction is only for payable cheques.');
        }

        return DB::transaction(function () use ($dto, $company) {
            // Logic to update next_number if using chequebook
            if ($dto->chequebook_id) {
                $chequebook = Chequebook::where('company_id', $company->id)
                    ->findOrFail($dto->chequebook_id);

                // If the manually entered number matches the next expected number, increment it
                if ($dto->cheque_number == $chequebook->next_number) {
                    $chequebook->increment('next_number');
                }
            }

            // Calculate Base Currency Amount if distinct
            $amountCompanyCurrency = $dto->amount;
            if ($dto->currency_id !== $company->currency_id) {
                // Assuming simple conversion or provided by UI.
                // For now, strict architectural pattern requires CurrencyConverterService,
                // but DTO doesn't have rate.
                // We will rely on Service layer or assume same currency for simplicity in this step,
                // OR fetch rate here.
                // Let's instantiate CurrencyConverter to be safe and correct.
                $converter = app(\Kezi\Foundation\Services\CurrencyConverterService::class);
                $currency = \Kezi\Foundation\Models\Currency::findOrFail($dto->currency_id);
                $rate = $converter->getExchangeRate($currency, Carbon::parse($dto->issue_date), $company) ?? 1.0;
                $amountCompanyCurrency = $dto->amount->multipliedBy($rate, \Brick\Math\RoundingMode::HALF_UP);
            }

            return Cheque::create([
                'company_id' => $dto->company_id,
                'chequebook_id' => $dto->chequebook_id,
                'journal_id' => $dto->journal_id,
                'partner_id' => $dto->partner_id,
                'currency_id' => $dto->currency_id,
                'payment_id' => $dto->payment_id,
                'cheque_number' => $dto->cheque_number,
                'amount' => $dto->amount,
                'amount_company_currency' => $amountCompanyCurrency,
                'issue_date' => $dto->issue_date,
                'due_date' => $dto->due_date,
                'type' => ChequeType::Payable,
                'status' => ChequeStatus::Draft,
                'payee_name' => $dto->payee_name,
                'memo' => $dto->memo,
            ]);
        });
    }
}
