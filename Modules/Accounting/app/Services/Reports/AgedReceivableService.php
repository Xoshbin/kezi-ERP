<?php

namespace Modules\Accounting\Services\Reports;

use App\DataTransferObjects\Reports\AgedReceivableDTO;
use App\DataTransferObjects\Reports\AgedReceivableLineDTO;
use App\Enums\Sales\InvoiceStatus;
use App\Models\Company;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AgedReceivableService
{
    public function generate(Company $company, Carbon $asOfDate): AgedReceivableDTO
    {
        $currency = $company->currency->code;
        $asOfDateString = $asOfDate->toDateString();

        // Get all posted invoices with their remaining amounts
        // We need to calculate remaining amounts by subtracting applied payments
        $query = DB::table('invoices')
            ->select([
                'partners.id as partner_id',
                'partners.name as partner_name',
                'invoices.id as invoice_id',
                'invoices.due_date',
                'invoices.total_amount',
                DB::raw("COALESCE(SUM(CASE
                    WHEN payments.status IN ('confirmed', 'reconciled')
                    THEN payment_document_links.amount_applied
                    ELSE 0
                END), 0) as total_paid"),
            ])
            ->join('partners', 'invoices.customer_id', '=', 'partners.id')
            ->leftJoin('payment_document_links', 'invoices.id', '=', 'payment_document_links.invoice_id')
            ->leftJoin('payments', 'payment_document_links.payment_id', '=', 'payments.id')
            ->where('invoices.company_id', $company->id)
            ->whereIn('invoices.status', [InvoiceStatus::Posted->value, InvoiceStatus::Paid->value])
            ->where('invoices.invoice_date', '<=', $asOfDateString)
            ->groupBy('partners.id', 'partners.name', 'invoices.id', 'invoices.due_date', 'invoices.total_amount');

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

            // Skip if fully paid or overpaid
            if ($remainingAmount->isZero() || $remainingAmount->isNegative()) {
                continue;
            }

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
                ];
            }

            // Calculate days past due
            $dueDate = Carbon::parse($row->due_date);
            $daysPastDue = $dueDate->diffInDays($asOfDate, false);

            // Categorize into aging buckets
            if ($daysPastDue < 0) {
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
                // 91+ days past due
                $partnerData[$partnerId]['bucket90_plus'] = $partnerData[$partnerId]['bucket90_plus']->plus($remainingAmount);
            }
        }

        // Convert to DTOs
        $reportLines = collect($partnerData)->map(function ($data) {
            $totalDue = $data['current']
                ->plus($data['bucket1_30'])
                ->plus($data['bucket31_60'])
                ->plus($data['bucket61_90'])
                ->plus($data['bucket90_plus']);

            return new AgedReceivableLineDTO(
                partnerId: $data['partnerId'],
                partnerName: $data['partnerName'],
                current: $data['current'],
                bucket1_30: $data['bucket1_30'],
                bucket31_60: $data['bucket31_60'],
                bucket61_90: $data['bucket61_90'],
                bucket90_plus: $data['bucket90_plus'],
                totalDue: $totalDue,
            );
        })->values();

        return $this->calculateTotals($reportLines, $currency);
    }

    /**
     * @param  Collection<int, AgedReceivableLineDTO>  $reportLines
     */
    private function calculateTotals(Collection $reportLines, string $currency): AgedReceivableDTO
    {
        $zero = Money::zero($currency);

        $totalCurrent = $reportLines->reduce(fn (Money $carry, $line) => $carry->plus($line->current), $zero);
        $totalBucket1_30 = $reportLines->reduce(fn (Money $carry, $line) => $carry->plus($line->bucket1_30), $zero);
        $totalBucket31_60 = $reportLines->reduce(fn (Money $carry, $line) => $carry->plus($line->bucket31_60), $zero);
        $totalBucket61_90 = $reportLines->reduce(fn (Money $carry, $line) => $carry->plus($line->bucket61_90), $zero);
        $totalBucket90_plus = $reportLines->reduce(fn (Money $carry, $line) => $carry->plus($line->bucket90_plus), $zero);
        $grandTotalDue = $reportLines->reduce(fn (Money $carry, $line) => $carry->plus($line->totalDue), $zero);

        return new AgedReceivableDTO(
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
