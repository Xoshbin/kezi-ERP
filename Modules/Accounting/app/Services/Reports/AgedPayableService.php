<?php

namespace Modules\Accounting\Services\Reports;

use Carbon\Carbon;
use Brick\Money\Money;
use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Accounting\DataTransferObjects\Reports\AgedPayableDTO;
use Modules\Accounting\DataTransferObjects\Reports\AgedPayableLineDTO;

class AgedPayableService
{
    public function generate(Company $company, Carbon $asOfDate): AgedPayableDTO
    {
        $currency = $company->currency->code;
        $asOfDateString = $asOfDate->toDateString();

        // Get all posted vendor bills with their remaining amounts
        // We need to calculate remaining amounts by subtracting applied payments
        $query = DB::table('vendor_bills')
            ->select([
                'partners.id as partner_id',
                'partners.name as partner_name',
                'vendor_bills.id as vendor_bill_id',
                'vendor_bills.due_date',
                'vendor_bills.total_amount',
                DB::raw("COALESCE(SUM(CASE
                    WHEN payments.status IN ('confirmed', 'reconciled')
                    THEN payment_document_links.amount_applied
                    ELSE 0
                END), 0) as total_paid"),
            ])
            ->join('partners', 'vendor_bills.vendor_id', '=', 'partners.id')
            ->leftJoin('payment_document_links', 'vendor_bills.id', '=', 'payment_document_links.vendor_bill_id')
            ->leftJoin('payments', 'payment_document_links.payment_id', '=', 'payments.id')
            ->where('vendor_bills.company_id', $company->id)
            ->whereIn('vendor_bills.status', [VendorBillStatus::Posted->value, VendorBillStatus::Paid->value])
            ->where('vendor_bills.bill_date', '<=', $asOfDateString)
            ->groupBy('partners.id', 'partners.name', 'vendor_bills.id', 'vendor_bills.due_date', 'vendor_bills.total_amount');

        $results = $query->get();

        // Process results to calculate aging buckets
        $partnerData = [];

        foreach ($results as $row) {
            $partnerId = $row->partner_id;
            $partnerName = $row->partner_name;

            // Calculate remaining amount (total - paid)
            // Database values are already in minor units (stored by MoneyCast)
            $totalAmount = Money::ofMinor($row->total_amount, $currency);
            $paidAmount = Money::ofMinor($row->total_paid, $currency);
            $remainingAmount = $totalAmount->minus($paidAmount);

            // Don't skip fully paid or overpaid vendors - show them with appropriate amounts
            // This ensures the report reconciles with General Ledger balances

            // Initialize partner data if not exists
            if (! isset($partnerData[$partnerId])) {
                $partnerData[$partnerId] = [
                    'partnerId' => $partnerId,
                    'partnerName' => $partnerName,
                    'current' => Money::zero($currency),
                    'bucket1_30' => Money::zero($currency),
                    'bucket31_60' => Money::zero($currency),
                    'bucket61_90' => Money::zero($currency),
                    'bucket90_plus' => Money::zero($currency),
                    'totalDue' => Money::zero($currency),
                ];
            }

            // Handle aging buckets based on remaining amount
            if ($remainingAmount->isZero()) {
                // Fully paid - no amounts in any bucket (already initialized to zero)
                continue;
            } elseif ($remainingAmount->isNegative()) {
                // Overpaid - show negative total due but zero in aging buckets
                // The negative amount represents credit balance (they owe us money back)
                continue; // Buckets remain zero, but partner will show negative totalDue
            } else {
                // Positive remaining amount - categorize by aging
                $dueDate = Carbon::parse($row->due_date);
                $daysPastDue = $dueDate->diffInDays($asOfDate, false);

                if ($daysPastDue <= 0) {
                    // Current (not yet due)
                    $partnerData[$partnerId]['current'] = $partnerData[$partnerId]['current']->plus($remainingAmount);
                } elseif ($daysPastDue <= 30) {
                    // 1-30 days past due
                    $partnerData[$partnerId]['bucket1_30'] = $partnerData[$partnerId]['bucket1_30']->plus($remainingAmount);
                } elseif ($daysPastDue <= 60) {
                    // 31-60 days past due
                    $partnerData[$partnerId]['bucket31_60'] = $partnerData[$partnerId]['bucket31_60']->plus($remainingAmount);
                } elseif ($daysPastDue <= 90) {
                    // 61-90 days past due
                    $partnerData[$partnerId]['bucket61_90'] = $partnerData[$partnerId]['bucket61_90']->plus($remainingAmount);
                } else {
                    // 90+ days past due
                    $partnerData[$partnerId]['bucket90_plus'] = $partnerData[$partnerId]['bucket90_plus']->plus($remainingAmount);
                }
            }
        }

        // Calculate total due for each partner by summing all their bills
        foreach ($partnerData as $partnerId => &$data) {
            $partnerTotalDue = Money::zero($currency);

            // Get all bills for this partner and calculate net amount
            $partnerBills = $results->where('partner_id', $partnerId);
            foreach ($partnerBills as $bill) {
                $billTotal = Money::ofMinor($bill->total_amount, $currency);
                $billPaid = Money::ofMinor($bill->total_paid, $currency);
                $billRemaining = $billTotal->minus($billPaid);
                $partnerTotalDue = $partnerTotalDue->plus($billRemaining);
            }

            $data['totalDue'] = $partnerTotalDue;
        }

        // Convert to DTOs
        $reportLines = collect($partnerData)->map(function ($data) {
            return new AgedPayableLineDTO(
                partnerId: $data['partnerId'],
                partnerName: $data['partnerName'],
                current: $data['current'],
                bucket1_30: $data['bucket1_30'],
                bucket31_60: $data['bucket31_60'],
                bucket61_90: $data['bucket61_90'],
                bucket90_plus: $data['bucket90_plus'],
                totalDue: $data['totalDue'],
            );
        })->values();

        return $this->calculateTotals($reportLines, $currency);
    }

    /**
     * @param  Collection<int, AgedPayableLineDTO>  $reportLines
     */
    private function calculateTotals(Collection $reportLines, string $currency): AgedPayableDTO
    {
        $zero = Money::zero($currency);

        $totalCurrent = $reportLines->reduce(fn(Money $carry, $line) => $carry->plus($line->current), $zero);
        $totalBucket1_30 = $reportLines->reduce(fn(Money $carry, $line) => $carry->plus($line->bucket1_30), $zero);
        $totalBucket31_60 = $reportLines->reduce(fn(Money $carry, $line) => $carry->plus($line->bucket31_60), $zero);
        $totalBucket61_90 = $reportLines->reduce(fn(Money $carry, $line) => $carry->plus($line->bucket61_90), $zero);
        $totalBucket90_plus = $reportLines->reduce(fn(Money $carry, $line) => $carry->plus($line->bucket90_plus), $zero);
        $grandTotalDue = $reportLines->reduce(fn(Money $carry, $line) => $carry->plus($line->totalDue), $zero);

        return new AgedPayableDTO(
            $reportLines,
            $totalCurrent,
            $totalBucket1_30,
            $totalBucket31_60,
            $totalBucket61_90,
            $totalBucket90_plus,
            $grandTotalDue
        );
    }
}
