<?php

namespace Kezi\Accounting\Services\Reports;

use App\Models\Company;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Kezi\Accounting\DataTransferObjects\Reports\TaxReportDTO;
use Kezi\Accounting\DataTransferObjects\Reports\TaxReportLineDTO;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Accounting\Models\Tax;

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

            $currentData = $taxData->get($taxKey);

            // Calculate the tax amount from the journal line based on tax type
            // Sales Tax (Liability): Credit increases, Debit decreases (Credit - Debit)
            // Purchase Tax (Asset): Debit increases, Credit decreases (Debit - Credit)
            if ($tax->isSalesTax()) {
                $lineTaxAmount = $taxLine->credit->minus($taxLine->debit);
            } else {
                $lineTaxAmount = $taxLine->debit->minus($taxLine->credit);
            }

            // Accumulate the tax amount
            $currentData['tax_amount'] = $currentData['tax_amount']->plus($lineTaxAmount);

            // Calculate net amount using the same logic for consistency
            // Net amount is derived from tax amount / rate
            if ($tax->rate > 0) {
                $currentData['net_amount'] = $currentData['tax_amount']->multipliedBy(100)->dividedBy($tax->rate, RoundingMode::HALF_UP);
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

    public function generateSpecificReport(string $generatorClass, Company $company, Carbon $startDate, Carbon $endDate): array
    {
        if (! class_exists($generatorClass)) {
            throw new \RuntimeException("Tax Report Generator class {$generatorClass} not found.");
        }

        $generator = app($generatorClass);

        if (! $generator instanceof \Kezi\Accounting\Services\Reports\Generators\TaxReportGeneratorContract) {
            throw new \RuntimeException("Class {$generatorClass} must implement TaxReportGeneratorContract.");
        }

        return $generator->generate($company, $startDate, $endDate);
    }
}
