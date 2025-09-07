<?php

namespace App\Actions\Loans;

use App\Models\LoanAgreement;
use App\Services\Loans\EIRSolverService;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;

class CalculateEIRAction
{
    public function __construct(private readonly EIRSolverService $eir) {}

    public function execute(LoanAgreement $loan): void
    {
        DB::transaction(function () use ($loan) {
            $loan->loadMissing('currency', 'feeLines', 'scheduleEntries');
            $currencyModel = $loan->currency;
            if (! $currencyModel) {
                throw new \RuntimeException('Loan currency missing');
            }
            $currency = (string) data_get($currencyModel, 'code');

            // Build cashflows: t0 is disbursement net of capitalized fees
            /** @var Money $principal */
            $principal = $loan->principal_amount;

            /** @var \Illuminate\Support\Collection<int, \App\Models\LoanFeeLine> $feeCollection */
            $feeCollection = $loan->feeLines;
            $capitalizedFees = Money::of('0', $currency);
            foreach ($feeCollection as $fee) {
                if ($fee->capitalize) {
                    /** @var \Brick\Money\Money $feeAmount */
                    $feeAmount = $fee->amount;
                    $capitalizedFees = $capitalizedFees->plus($feeAmount);
                }
            }

            $netDisbursement = $principal->minus($capitalizedFees);

            $cashflows = [];
            $cashflows[] = -$netDisbursement->getAmount()->toFloat();

            /** @var \Illuminate\Support\Collection<int, \App\Models\LoanScheduleEntry> $entriesCol */
            $entriesCol = $loan->scheduleEntries()->orderBy('sequence')->get();
            foreach ($entriesCol as $entry) {
                /** @var \Brick\Money\Money $pmt */
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
                /** @var \App\Models\LoanScheduleEntry $entry */
                $interest = Money::of($carrying->getAmount()->toFloat() * $rate, $currency, null, \Brick\Math\RoundingMode::HALF_UP);
                /** @var \Brick\Money\Money $payment */
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

