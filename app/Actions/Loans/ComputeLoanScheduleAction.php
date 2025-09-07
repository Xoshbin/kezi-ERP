<?php

namespace App\Actions\Loans;

use App\Enums\Loans\ScheduleMethod;
use App\Models\LoanAgreement;
use App\Models\LoanScheduleEntry;
use App\Services\Loans\InterestCalculatorService;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ComputeLoanScheduleAction
{
    public function __construct(private readonly InterestCalculatorService $interestCalc) {}

    public function execute(LoanAgreement $loan): void
    {
        DB::transaction(function () use ($loan) {
            $loan->loadMissing('currency', 'scheduleEntries');
            $loan->scheduleEntries()->delete();

            // Guard/annotate for static analysis
            /** @var Money $principal */
            $principal = $loan->principal_amount; // Money in loan currency via DocumentCurrencyMoneyCast
            $n = (int) $loan->duration_months; // monthly frequency for now
            $rAnnual = (float) $loan->interest_rate;
            $currencyModel = $loan->currency;
            if (! $currencyModel) {
                throw new \RuntimeException('Loan currency is missing');
            }
            /** @var string $currency */
            $currency = (string) data_get($currencyModel, 'code');

            $balance = $principal;
            $date = Carbon::parse($loan->start_date)->copy();

            for ($i = 1; $i <= $n; $i++) {
                $interest = Money::of('0', $currency);
                $principalComponent = Money::of('0', $currency);
                $paymentAmount = Money::of('0', $currency);

                // Monthly simple rate
                $periodRate = ($rAnnual / 100) / 12;

                if ($loan->schedule_method === ScheduleMethod::Annuity) {
                    $interest = Money::of($balance->getAmount()->toFloat() * $periodRate, $currency, null, RoundingMode::HALF_UP);
                    $paymentAmount = $this->interestCalc->annuityPayment($principal, $rAnnual, 12, $n);
                    $principalComponent = $paymentAmount->minus($interest);
                } elseif ($loan->schedule_method === ScheduleMethod::StraightLinePrincipal) {
                    $principalComponent = $principal->dividedBy($n, RoundingMode::HALF_UP);
                    $interest = Money::of($balance->getAmount()->toFloat() * $periodRate, $currency, null, RoundingMode::HALF_UP);
                    $paymentAmount = $principalComponent->plus($interest);
                }

                $balance = $balance->minus($principalComponent);

                $entry = new LoanScheduleEntry();
                $entry->loan()->associate($loan); // ensure DocumentCurrencyMoneyCast can resolve currency
                $entry->sequence = $i;
                $entry->due_date = $date->copy()->addMonths($i);
                $entry->payment_amount = $paymentAmount;
                $entry->principal_component = $principalComponent;
                $entry->interest_component = $interest;
                $entry->outstanding_balance_after = $balance->isNegative() ? Money::of(0, $currency) : $balance;
                $entry->save();
            }
        });
    }
}

