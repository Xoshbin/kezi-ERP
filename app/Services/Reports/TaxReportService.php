<?php

namespace App\Services\Reports;

use App\DataTransferObjects\Reports\TaxReportDTO;
use App\DataTransferObjects\Reports\TaxReportLineDTO;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\Tax;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TaxReportService
{
    public function generate(Company $company, Carbon $startDate, Carbon $endDate): TaxReportDTO
    {
        $currency = $company->currency->code;
        $zero = Money::zero($currency);

        // Get all taxes for this company to establish the structure
        $taxes = Tax::where('company_id', $company->id)
            ->where('is_active', true)
            ->with('taxAccount')
            ->get();

        // Initialize collections for aggregated data
        /** @var Collection<string, array<string, mixed>> $taxData */
        $taxData = new Collection;

        // Get all posted journal entries in the period from sale and purchase journals
        $journalEntries = JournalEntry::query()
            ->with(['lines.account.taxes', 'journal'])
            ->where('company_id', $company->id)
            ->where('is_posted', true)
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->whereHas('journal', fn ($q) => $q->whereIn('type', ['sale', 'purchase']))
            ->get();

        // Process each journal entry to extract tax information
        foreach ($journalEntries as $entry) {
            $this->processJournalEntry($entry, $taxes, $taxData, $currency);
        }

        return $this->buildReportFromTaxData($taxData, $currency);
    }

    /**
     * @param  Collection<int, Tax>  $taxes
     * @param  Collection<string, array<string, mixed>>  $taxData
     */
    private function processJournalEntry(JournalEntry $entry, Collection $taxes, Collection $taxData, string $currency): void
    {
        // Get lines that are posted to tax accounts
        $taxLines = $entry->lines->filter(function ($line) use ($taxes) {
            return $taxes->contains(function ($tax) use ($line) {
                return $tax->tax_account_id === $line->account_id;
            });
        });

        foreach ($taxLines as $taxLine) {
            // Find the tax that corresponds to this account
            $tax = $taxes->first(fn ($t) => $t->tax_account_id === $taxLine->account_id);

            if (! $tax) {
                continue;
            }

            // Initialize tax data if not exists
            $taxKey = (string) $tax->id;
            if (! $taxData->has($taxKey)) {
                $taxData->put($taxKey, [
                    'tax' => $tax,
                    'net_amount' => Money::zero($currency),
                    'tax_amount' => Money::zero($currency),
                ]);
            }

            // Calculate the tax amount from the journal line
            // For sales tax: credit balance (positive)
            // For purchase tax: debit balance (negative, so we take absolute)
            $lineAmount = $taxLine->credit->minus($taxLine->debit);

            // Accumulate the tax amount
            $currentData = $taxData->get($taxKey);
            if (!$currentData || !isset($currentData['tax_amount'])) {
                throw new \Exception('Tax data not properly initialized');
            }
            $currentData['tax_amount'] = $currentData['tax_amount']->plus($lineAmount->abs());

            // Calculate net amount from tax amount and rate
            if ($tax->rate > 0) {
                $netFromTax = $currentData['tax_amount']->dividedBy($tax->rate / 100, RoundingMode::HALF_UP);
                $currentData['net_amount'] = $netFromTax;
            }

            $taxData->put($taxKey, $currentData);
        }
    }

    /**
     * @param  Collection<string, array<string, mixed>>  $taxData
     */
    private function buildReportFromTaxData(Collection $taxData, string $currency): TaxReportDTO
    {
        $zero = Money::zero($currency);
        $outputTaxLines = new Collection;
        $inputTaxLines = new Collection;

        foreach ($taxData as $data) {
            $tax = $data['tax'];
            $netAmount = $data['net_amount'];
            $taxAmount = $data['tax_amount'];

            $line = new TaxReportLineDTO(
                taxId: $tax->id,
                taxName: $tax->name,
                taxRate: $tax->rate,
                netAmount: $netAmount,
                taxAmount: $taxAmount
            );

            // Categorize by tax type
            if ($tax->isSalesTax()) {
                $outputTaxLines->push($line);
            } elseif ($tax->isPurchaseTax()) {
                $inputTaxLines->push($line);
            }
        }

        // Calculate totals
        $totalOutputTax = $outputTaxLines->reduce(
            fn (Money $carry, TaxReportLineDTO $line) => $carry->plus($line->taxAmount),
            $zero
        );

        $totalInputTax = $inputTaxLines->reduce(
            fn (Money $carry, TaxReportLineDTO $line) => $carry->plus($line->taxAmount),
            $zero
        );

        $netTaxPayable = $totalOutputTax->minus($totalInputTax);

        return new TaxReportDTO(
            outputTaxLines: $outputTaxLines,
            inputTaxLines: $inputTaxLines,
            totalOutputTax: $totalOutputTax,
            totalInputTax: $totalInputTax,
            netTaxPayable: $netTaxPayable
        );
    }
}
