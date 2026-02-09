<?php

namespace Kezi\Accounting\Services\Reports\Generators;

use App\Models\Company;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Carbon\Carbon;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Accounting\Models\Tax;

class IraqVATReturnGenerator implements TaxReportGeneratorContract
{
    public function generate(Company $company, Carbon $startDate, Carbon $endDate): array
    {
        $currency = $company->currency->code;
        $zero = Money::zero($currency);

        // Fetch relevant Journal Entries
        // We filter by taxes that have a 'report_tag' populated, as these are the ones mapped to the return.
        $journalEntries = JournalEntry::query()
            ->with(['lines.account', 'lines.tax'])
            ->where('company_id', $company->id)
            ->where('is_posted', true)
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->whereHas('lines.tax', function ($query) {
                $query->whereNotNull('report_tag');
            })
            ->get();

        // Initialize Boxes
        // Box 1: Sales Subject to VAT
        // Box 2: VAT on Sales
        // Box 3: Purchases Subject to VAT
        // Box 4: VAT on Purchases
        // Box 5: Net VAT Payable/Refundable

        // Structure: 'boxes' => [ '1' => ['label' => '...', 'amount' => Money], ... ]
        $boxes = [
            '1' => $zero, // Total Sales (Net)
            '2' => $zero, // Total VAT on Sales
            '3' => $zero, // Total Purchases (Net)
            '4' => $zero, // Total VAT on Purchases
        ];

        foreach ($journalEntries as $entry) {
            foreach ($entry->lines as $line) {
                if ($line->tax && $line->tax->report_tag) {
                    $tag = $line->tax->report_tag;

                    // Logic based on Tags
                    // Example Tags: 'VAT_SALES_STD', 'VAT_PURCHASE_STD'

                    if (str_starts_with($tag, 'VAT_SALES')) {
                        // For Sales: Credit is positive, Debit is negative (Refund)
                        $taxAmount = $line->credit->minus($line->debit);

                        // Net Amount?
                        // We usually don't store Net Amount on the Tax Line itself in JournalEntryLine.
                        // We might need to look at related income lines or infer from tax rate.
                        // Inferring from rate is safer if we assume the line represents the tax portion.
                        // Net = Tax / Rate
                        if ($line->tax->rate > 0) {
                            $rateDecimal = BigDecimal::of($line->tax->rate)->dividedBy(100, 4, RoundingMode::HALF_UP);
                            $netAmount = $taxAmount->dividedBy($rateDecimal, RoundingMode::HALF_UP);
                        } else {
                            $netAmount = $zero;
                        }

                        $boxes['1'] = $boxes['1']->plus($netAmount);
                        $boxes['2'] = $boxes['2']->plus($taxAmount);
                    } elseif (str_starts_with($tag, 'VAT_PURCHASE')) {
                        // For Purchase: Debit is positive (Input Tax), Credit is negative
                        $taxAmount = $line->debit->minus($line->credit);

                        if ($line->tax->rate > 0) {
                            $rateDecimal = BigDecimal::of($line->tax->rate)->dividedBy(100, 4, RoundingMode::HALF_UP);
                            $netAmount = $taxAmount->dividedBy($rateDecimal, RoundingMode::HALF_UP);
                        } else {
                            $netAmount = $zero;
                        }

                        $boxes['3'] = $boxes['3']->plus($netAmount);
                        $boxes['4'] = $boxes['4']->plus($taxAmount);
                    }
                }
            }
        }

        $netPayable = $boxes['2']->minus($boxes['4']);

        return [
            'report_name' => 'Iraq VAT Return',
            'period' => $startDate->format('Y-m-d').' to '.$endDate->format('Y-m-d'),
            'currency' => $currency,
            'boxes' => [
                '1' => ['label' => 'Total Sales Subject to VAT', 'value' => (string) $boxes['1']],
                '2' => ['label' => 'VAT on Sales (Output Tax)', 'value' => (string) $boxes['2']],
                '3' => ['label' => 'Total Purchases Subject to VAT', 'value' => (string) $boxes['3']],
                '4' => ['label' => 'VAT on Purchases (Input Tax)', 'value' => (string) $boxes['4']],
                '5' => ['label' => 'Net VAT Payable (Refundable)', 'value' => (string) $netPayable],
            ],
            'formatted' => [ // For display
                '1' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($boxes['1']),
                '2' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($boxes['2']),
                '3' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($boxes['3']),
                '4' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($boxes['4']),
                '5' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($netPayable),
            ],
            'is_refundable' => $netPayable->isNegative(),
        ];
    }
}
