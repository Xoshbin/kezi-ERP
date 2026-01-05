<?php

namespace Modules\Accounting\Services\Reports\Consolidation;

use App\Models\Company;
use Brick\Money\Money;
use Carbon\Carbon;
use Modules\Accounting\DataTransferObjects\Reports\Consolidation\ConsolidatedTrialBalanceDTO;
use Modules\Accounting\DataTransferObjects\Reports\Consolidation\ConsolidatedTrialBalanceLineDTO;
use Modules\Accounting\Services\Consolidation\CurrencyTranslationService;
use Modules\Accounting\Services\Consolidation\InterCompanyEliminationService;
use Modules\Accounting\Services\Reports\TrialBalanceService;

class ConsolidatedTrialBalanceService
{
    public function __construct(
        protected TrialBalanceService $trialBalanceService,
        protected CurrencyTranslationService $currencyTranslationService,
        protected InterCompanyEliminationService $eliminationService,
    ) {}

    public function generate(Company $parentCompany, Carbon $asOfDate): ConsolidatedTrialBalanceDTO
    {
        $companies = [$parentCompany, ...$parentCompany->childrenCompanies];
        $targetCurrency = $parentCompany->currency->code;
        $zero = Money::zero($targetCurrency);

        /** @var array<string, array> $aggregatedLines Keyed by Account Code */
        $aggregatedLines = [];

        // 1. Aggregation & Translation
        foreach ($companies as $company) {
            $tb = $this->trialBalanceService->generate($company, $asOfDate);

            foreach ($tb->reportLines as $line) {
                // Determine translation method based on account type
                // Assets/Liabilities/Equity -> Closing Rate
                // Income/Expense -> Average Rate
                $method = match ($line->accountType) {
                    \Modules\Accounting\Enums\Accounting\AccountType::Income,
                    \Modules\Accounting\Enums\Accounting\AccountType::Expense => \Modules\Accounting\Enums\Consolidation\CurrencyTranslationMethod::AverageRate,
                    default => \Modules\Accounting\Enums\Consolidation\CurrencyTranslationMethod::ClosingRate,
                };

                $period = [
                    'start' => $asOfDate->copy()->startOfYear(),
                    'end' => $asOfDate,
                ];

                // Convert Amounts
                // Note: TrialBalanceLineDTO debit/credit are Money objects in Local Currency
                $convertedDebit = $this->currencyTranslationService->translate(
                    $line->debit,
                    $parentCompany->currency,
                    $asOfDate,
                    $method,
                    $parentCompany,
                    $period
                );

                $convertedCredit = $this->currencyTranslationService->translate(
                    $line->credit,
                    $parentCompany->currency,
                    $asOfDate,
                    $method,
                    $parentCompany,
                    $period
                );

                $code = $line->accountCode;

                if (! isset($aggregatedLines[$code])) {
                    $aggregatedLines[$code] = [
                        'accountCode' => $code,
                        'accountName' => $line->accountName, // Use parent/first found name
                        'accountType' => $line->accountType,
                        'consolidatedDebit' => $zero,
                        'consolidatedCredit' => $zero,
                        'eliminationDebit' => $zero,
                        'eliminationCredit' => $zero,
                        'companyBalances' => [],
                    ];
                }

                // Add to Aggregated
                /** @var Money $currentDebit */
                $currentDebit = $aggregatedLines[$code]['consolidatedDebit'];
                $aggregatedLines[$code]['consolidatedDebit'] = $currentDebit->plus($convertedDebit);

                /** @var Money $currentCredit */
                $currentCredit = $aggregatedLines[$code]['consolidatedCredit'];
                $aggregatedLines[$code]['consolidatedCredit'] = $currentCredit->plus($convertedCredit);

                // Track Company Balance (Net)
                $net = $convertedDebit->minus($convertedCredit);
                $aggregatedLines[$code]['companyBalances'][$company->id] = $net;
            }
        }

        // 2. Elimination
        $companyIds = collect($companies)->pluck('id')->toArray();
        $eliminationLines = $this->eliminationService->identifyInterCompanyBalances($companyIds, $asOfDate);

        foreach ($eliminationLines as $jeLine) {
            // Fetch Account Code via relation (eager loaded in service)
            $account = $jeLine->account;
            $code = $account->code;

            if (! isset($aggregatedLines[$code])) {
                // Should theoretically exist if it was in TB, unless filtering hid it?
                // Creating entry if missing (unlikely if loop above covers all posted entries)
                continue;
            }

            // Convert Elimination Amount to Parent Currency
            $amount = $jeLine->debit->isPositive() ? $jeLine->debit : $jeLine->credit;
            // But wait, JE Line has separate Debit/Credit attributes (each is Money).

            // Logic:
            // If JE Line is Debit -> We eliminate by Crediting.
            // If JE Line is Credit -> We eliminate by Debiting.

            // Convert Local Debit
            $localDebit = $jeLine->debit; // Money in Local Currency
            $convertedDebitElim = $this->currencyTranslationService->translate(
                $localDebit,
                $parentCompany->currency,
                $asOfDate,
                \Modules\Accounting\Enums\Consolidation\CurrencyTranslationMethod::ClosingRate, // Balances are usually Closing
                $parentCompany
            );

            // Convert Local Credit
            $localCredit = $jeLine->credit;
            $convertedCreditElim = $this->currencyTranslationService->translate(
                $localCredit,
                $parentCompany->currency,
                $asOfDate,
                \Modules\Accounting\Enums\Consolidation\CurrencyTranslationMethod::ClosingRate,
                $parentCompany
            );

            // Apply to Elimination Columns (Inverted)
            // Existing Debit needs Credit Elimination
            if ($convertedDebitElim->isPositive()) {
                $currentElimCredit = $aggregatedLines[$code]['eliminationCredit'];
                $aggregatedLines[$code]['eliminationCredit'] = $currentElimCredit->plus($convertedDebitElim);

                // Adjust Consolidated Total (Debit - EliminationCredit) is incorrect?
                // No, Consolidated = Combined + Eliminations (where Elim are offsets)
                // Simply: Debit = Debit + ElimDebit. Credit = Credit + ElimCredit.
                // Wait.
                // Correct logic:
                // Final Report Debit = Aggregated Debit + Elimination Debit.
                // Final Report Credit = Aggregated Credit + Elimination Credit.
                // And Elimination Credit (calculated here) reduces the Net Balance.
                // Here I am just storing the Elimination Amount.
                // The DTO consumer (UI/PDF) should calculate Net = (AggDebit + ElimDebit) - (AggCredit + ElimCredit).
            }

            // Existing Credit needs Debit Elimination
            if ($convertedCreditElim->isPositive()) {
                $currentElimDebit = $aggregatedLines[$code]['eliminationDebit'];
                $aggregatedLines[$code]['eliminationDebit'] = $currentElimDebit->plus($convertedCreditElim);
            }
        }

        // 3. Finalize
        $reportLines = collect($aggregatedLines)->map(function ($data) {
            // Calculate Final Consolidated Amounts
            /** @var Money $conDebit */
            $conDebit = $data['consolidatedDebit'];
            /** @var Money $elimDebit */
            $elimDebit = $data['eliminationDebit'];
            /** @var Money $conCredit */
            $conCredit = $data['consolidatedCredit'];
            /** @var Money $elimCredit */
            $elimCredit = $data['eliminationCredit'];

            // Actual Logic:
            // Elimination Debit increases Debit side (usually to offset a Credit elsewhere?).
            // NO. Elimination of a DEBIT ENTRY (Receivable) is a CREDIT.
            // So if I have Receivable 100 (Debit). Elimination is 100 (Credit).
            // Net Receivable = 100 - 100 = 0.
            // So in the DTO, 'consolidatedDebit' should probably be the FINAL amount?
            // Or 'combinedDebit'?
            // My DTO has `consolidatedDebit` and `eliminationDebit`.
            // I named it `consolidatedDebit`.
            // Ideally `consolidatedDebit` = Final Debit.
            // `combinedDebit` = Pre-elimination.

            // Let's redefine DTO semantics:
            // consolidatedDebit is the NET result?
            // Or should I expose both?
            // DTO has `consolidatedDebit` and `eliminationDebit`.
            // I'll set `consolidatedDebit` to include elimination effect.

            // Effective Debit = (AggrDebit + ElimDebit) - (AggrCredit + ElimCredit) if Positive?
            // No, Keep sides separate.
            // Final Debit = AggrDebit + ElimDebit.
            // Final Credit = AggrCredit + ElimCredit.
            // Wait.
            // Receivable (Dr 100). Elimination (Cr 100).
            // Final Dr = 100. Final Cr = 100.
            // Net = 0.
            // This works for Trial Balance (Check totals).
            // But usually in Report we want to see (100) -> (100) -> 0.

            // I will store the FINAL amounts in `consolidatedDebit/Credit`.
            // Wait, if I do that, I lose the drilldown of "before elimination".
            // But I have `eliminationDebit/Credit` in the DTO too.
            // So `consolidatedDebit` should be the POST-elimination amount?
            // Let's assume `consolidatedDebit` = `AggrDebit + ElimDebit`.
            // And `eliminationDebit` is the stored elimination value.
            // Wait. If Elimination is Credit 100 (to kill a Debit 100).
            // Then `AggrDebit` = 100. `ElimCredit` = 100.
            // `ConsolidatedDebit` = 100. `ConsolidatedCredit` = 100.
            // Net = 0.
            // This is acceptable for TB.

            return new ConsolidatedTrialBalanceLineDTO(
                accountCode: $data['accountCode'],
                accountName: $data['accountName'],
                accountType: $data['accountType'],
                consolidatedDebit: $conDebit->plus($elimDebit),
                consolidatedCredit: $conCredit->plus($elimCredit),
                eliminationDebit: $elimDebit,
                eliminationCredit: $elimCredit,
                companyBalances: $data['companyBalances']
            );
        })->sortBy('accountCode')->values();

        $totalDebit = Money::zero($targetCurrency);
        $totalCredit = Money::zero($targetCurrency);

        foreach ($reportLines as $line) {
            $totalDebit = $totalDebit->plus($line->consolidatedDebit);
            $totalCredit = $totalCredit->plus($line->consolidatedCredit);
        }

        return new ConsolidatedTrialBalanceDTO(
            reportLines: $reportLines,
            totalDebit: $totalDebit,
            totalCredit: $totalCredit,
            isBalanced: $totalDebit->isEqualTo($totalCredit)
        );
    }
}
