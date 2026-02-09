<?php

namespace Kezi\Accounting\Actions\Loans;

use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Kezi\Accounting\Enums\Loans\ScheduleMethod;
use Kezi\Accounting\Models\LoanAgreement;
use Kezi\Accounting\Models\LoanRateChange;
use Kezi\Accounting\Models\LoanScheduleEntry;
use RuntimeException;

class ComputeLoanScheduleAction
{
    public function __construct(private readonly \Kezi\Accounting\Services\Loans\InterestCalculatorService $interestCalc) {}

    public function execute(LoanAgreement $loan): void
    {
        DB::transaction(function () use ($loan) {
            $loan->loadMissing('currency', 'scheduleEntries', 'rateChanges');
            $loan->scheduleEntries()->delete();

            // Guard/annotate for static analysis
            /** @var Money $principal */
            $principal = $loan->principal_amount; // Money in loan currency via DocumentCurrencyMoneyCast
            $n = (int) $loan->duration_months; // monthly frequency for now
            $currencyModel = $loan->currency;
            if (! $currencyModel) {
                throw new RuntimeException('Loan currency is missing');
            }
            /** @var string $currency */
            $currency = (string) data_get($currencyModel, 'code');

            $balance = $principal;
            $date = Carbon::parse($loan->start_date)->copy();

            // Prepare rate changes lookup by month index (1-based)
            $rateByMonth = [];
            foreach ($loan->rateChanges as $rc) {
                /** @var LoanRateChange $rc */
                $effective = Carbon::parse($rc->effective_date);
                $monthIndex = $date->diffInMonths($effective) + 1; // apply to installment whose due date covers period starting at effective
                $monthIndex = max(1, min($n, $monthIndex));
                $rateByMonth[$monthIndex] = (float) $rc->annual_rate;
            }

            $currentAnnualRate = (float) $loan->interest_rate;

            for ($i = 1; $i <= $n; $i++) {
                if (isset($rateByMonth[$i])) {
                    $currentAnnualRate = $rateByMonth[$i];
                }

                // Monthly simple rate (BigDecimal for precision)
                $periodRate = \Brick\Math\BigDecimal::of($currentAnnualRate)
                    ->dividedBy(100, 10, RoundingMode::HALF_UP)
                    ->dividedBy(12, 10, RoundingMode::HALF_UP);

                $interest = $balance->multipliedBy($periodRate, RoundingMode::HALF_UP);

                if ($loan->schedule_method === ScheduleMethod::Annuity) {
                    $remaining = $n - $i + 1;
                    $paymentAmount = $this->interestCalc->annuityPayment($balance, $currentAnnualRate, 12, $remaining);
                    $principalComponent = $paymentAmount->minus($interest);
                } else { // StraightLinePrincipal
                    $principalComponent = $principal->dividedBy($n, RoundingMode::HALF_UP);
                    $paymentAmount = $principalComponent->plus($interest);
                }

                $balance = $balance->minus($principalComponent);

                $entry = new LoanScheduleEntry;
                $entry->loan()->associate($loan);
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
