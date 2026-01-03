<?php

namespace Modules\Accounting\Services\Reports;

use App\Models\Company;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\DataTransferObjects\Reports\FxGainLossLineDTO;
use Modules\Accounting\DataTransferObjects\Reports\FxGainLossReportDTO;
use Modules\Accounting\Enums\Accounting\JournalEntryState;
use Modules\Accounting\Models\CurrencyRevaluation;

/**
 * FxGainLossReportService
 *
 * Generates comprehensive FX gain/loss reports showing both realized
 * and unrealized foreign exchange gains and losses.
 */
class FxGainLossReportService
{
    /**
     * Generate the FX Gain/Loss report for a company within a date range.
     */
    public function generate(Company $company, Carbon $startDate, Carbon $endDate): FxGainLossReportDTO
    {
        $currencyCode = $company->currency->code;
        $zero = Money::zero($currencyCode);

        // Get realized gains/losses from journal entries
        $realizedLines = $this->getRealizedGainsLosses($company, $startDate, $endDate);

        // Get unrealized gains/losses from revaluations
        $unrealizedLines = $this->getUnrealizedGainsLosses($company, $startDate, $endDate);

        // Calculate totals for realized
        $totalRealizedGain = $zero;
        $totalRealizedLoss = $zero;
        foreach ($realizedLines as $line) {
            if ($line->isGain()) {
                $totalRealizedGain = $totalRealizedGain->plus($line->gain_loss_amount);
            } else {
                $totalRealizedLoss = $totalRealizedLoss->plus($line->gain_loss_amount->abs());
            }
        }
        $netRealized = $totalRealizedGain->minus($totalRealizedLoss);

        // Calculate totals for unrealized
        $totalUnrealizedGain = $zero;
        $totalUnrealizedLoss = $zero;
        foreach ($unrealizedLines as $line) {
            if ($line->isGain()) {
                $totalUnrealizedGain = $totalUnrealizedGain->plus($line->gain_loss_amount);
            } else {
                $totalUnrealizedLoss = $totalUnrealizedLoss->plus($line->gain_loss_amount->abs());
            }
        }
        $netUnrealized = $totalUnrealizedGain->minus($totalUnrealizedLoss);

        // Total net FX impact
        $totalNetFxImpact = $netRealized->plus($netUnrealized);

        return new FxGainLossReportDTO(
            company_name: $company->name,
            base_currency: $currencyCode,
            start_date: $startDate->toDateString(),
            end_date: $endDate->toDateString(),
            realized_gains_losses: $realizedLines,
            unrealized_gains_losses: $unrealizedLines,
            total_realized_gain: $totalRealizedGain,
            total_realized_loss: $totalRealizedLoss,
            net_realized: $netRealized,
            total_unrealized_gain: $totalUnrealizedGain,
            total_unrealized_loss: $totalUnrealizedLoss,
            net_unrealized: $netUnrealized,
            total_net_fx_impact: $totalNetFxImpact,
        );
    }

    /**
     * Get realized FX gains/losses from journal entries.
     *
     * @return Collection<int, FxGainLossLineDTO>
     */
    protected function getRealizedGainsLosses(Company $company, Carbon $startDate, Carbon $endDate): Collection
    {
        $currencyCode = $company->currency->code;
        $gainLossAccountId = $company->default_gain_loss_account_id;

        if (! $gainLossAccountId) {
            return collect();
        }

        // Query journal entry lines that hit the gain/loss account
        $results = DB::table('journal_entry_lines as jel')
            ->select([
                'je.entry_date',
                'je.reference',
                'jel.description',
                'jel.debit',
                'jel.credit',
                'je.source_type',
                'je.source_id',
            ])
            ->join('journal_entries as je', 'jel.journal_entry_id', '=', 'je.id')
            ->where('jel.account_id', $gainLossAccountId)
            ->where('je.company_id', $company->id)
            ->where('je.state', JournalEntryState::Posted->value)
            ->whereBetween('je.entry_date', [$startDate->toDateString(), $endDate->toDateString()])
            // Exclude revaluation entries (those are unrealized)
            ->where(function ($query) {
                $query->whereNull('je.source_type')
                    ->orWhere('je.source_type', '!=', CurrencyRevaluation::class);
            })
            ->orderBy('je.entry_date')
            ->get();

        return $results->map(function ($row) use ($currencyCode) {
            $debit = (int) ($row->debit ?? 0);
            $credit = (int) ($row->credit ?? 0);
            // Credit to gain/loss account = gain, Debit = loss
            $amount = $credit - $debit;

            return new FxGainLossLineDTO(
                date: $row->entry_date,
                reference: $row->reference ?? '',
                description: $row->description ?? 'FX Gain/Loss',
                currency_code: $currencyCode,
                foreign_amount: Money::zero($currencyCode),
                original_rate: 0.0,
                settlement_rate: 0.0,
                gain_loss_amount: Money::ofMinor($amount, $currencyCode),
                type: 'realized',
                source_id: $row->source_id,
                source_type: $row->source_type,
            );
        })->filter(fn ($line) => ! $line->gain_loss_amount->isZero());
    }

    /**
     * Get unrealized FX gains/losses from revaluations.
     *
     * @return Collection<int, FxGainLossLineDTO>
     */
    protected function getUnrealizedGainsLosses(Company $company, Carbon $startDate, Carbon $endDate): Collection
    {
        $currencyCode = $company->currency->code;

        // Get posted revaluations within the date range
        $revaluations = CurrencyRevaluation::with(['lines.account', 'lines.currency'])
            ->where('company_id', $company->id)
            ->whereBetween('revaluation_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $lines = collect();

        foreach ($revaluations as $revaluation) {
            foreach ($revaluation->lines as $line) {
                $lines->push(new FxGainLossLineDTO(
                    date: $revaluation->revaluation_date->toDateString(),
                    reference: $revaluation->reference ?? '',
                    description: "Revaluation - {$line->account->code}",
                    currency_code: $line->currency->code ?? $currencyCode,
                    foreign_amount: $line->foreign_currency_balance,
                    original_rate: $line->historical_rate,
                    settlement_rate: $line->current_rate,
                    gain_loss_amount: $line->adjustment_amount,
                    type: 'unrealized',
                    account_code: $line->account->code ?? null,
                    account_name: $line->account->name ?? null,
                ));
            }
        }

        return $lines->filter(fn ($line) => ! $line->gain_loss_amount->isZero());
    }
}

