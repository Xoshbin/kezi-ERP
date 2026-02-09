<?php

namespace Kezi\Accounting\Actions\Loans;

use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Kezi\Accounting\Models\LoanAgreement;
use Kezi\Accounting\Models\LoanFeeLine;
use Kezi\Accounting\Models\LoanScheduleEntry;
use RuntimeException;

class CalculateEIRAction
{
    public function __construct(private readonly \Kezi\Accounting\Services\Loans\EIRSolverService $eir) {}

    public function execute(LoanAgreement $loan): void
    {
        DB::transaction(function () use ($loan) {
            $loan->loadMissing('currency', 'feeLines', 'scheduleEntries');
            $currencyModel = $loan->currency;
            if (! $currencyModel) {
                throw new RuntimeException('Loan currency missing');
            }
            $currency = (string) data_get($currencyModel, 'code');

            // Build cashflows: t0 is disbursement net of capitalized fees
            /** @var Money $principal */
            $principal = $loan->principal_amount;

            /** @var Collection<int, LoanFeeLine> $feeCollection */
            $feeCollection = $loan->feeLines;
            $capitalizedFees = Money::of('0', $currency);
            foreach ($feeCollection as $fee) {
                if ($fee->capitalize) {
                    /** @var Money $feeAmount */
                    $feeAmount = $fee->amount;
                    $capitalizedFees = $capitalizedFees->plus($feeAmount);
                }
            }

            $netDisbursement = $principal->minus($capitalizedFees);

            $cashflows = [];
            $cashflows[] = -$netDisbursement->getAmount()->toFloat();

            /** @var Collection<int, LoanScheduleEntry> $entriesCol */
            $entriesCol = $loan->scheduleEntries()->orderBy('sequence')->get();
            foreach ($entriesCol as $entry) {
                /** @var Money $pmt */
                $pmt = $entry->payment_amount;
                $cashflows[] = $pmt->getAmount()->toFloat();
            }

            $rate = $this->eir->solvePeriodicIRR($cashflows);
            $loan->eir_rate = $rate;
            $loan->save();

            if (! $loan->eir_enabled) {
                return;
            }

            // Recompute interest components using EIR rate on carrying amount
            $carrying = $netDisbursement;
            $entries = $loan->scheduleEntries()->orderBy('sequence')->get();
            foreach ($entries as $entry) {
                /** @var LoanScheduleEntry $entry */
                $interest = $carrying->multipliedBy($rate, RoundingMode::HALF_UP);
                /** @var Money $payment */
                $payment = $entry->payment_amount;
                $principalComponent = $payment->minus($interest);
                $carrying = $carrying->minus($principalComponent);

                $entry->interest_component = $interest;
                $entry->principal_component = $principalComponent;
                $entry->outstanding_balance_after = $carrying->isNegative() ? Money::of('0', $currency) : $carrying;
                $entry->save();
            }
        });
    }
}
