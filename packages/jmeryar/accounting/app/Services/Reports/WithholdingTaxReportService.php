<?php

namespace Jmeryar\Accounting\Services\Reports;

use App\Models\Company;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Jmeryar\Accounting\DataTransferObjects\Reports\WithholdingTaxReportDTO;
use Jmeryar\Accounting\DataTransferObjects\Reports\WithholdingTaxReportLineDTO;
use Jmeryar\Accounting\DataTransferObjects\Reports\WithholdingTaxReportTypeLineDTO;
use Jmeryar\Accounting\Models\WithholdingTaxCertificate;
use Jmeryar\Accounting\Models\WithholdingTaxEntry;

class WithholdingTaxReportService
{
    public function generate(Company $company, Carbon $startDate, Carbon $endDate): WithholdingTaxReportDTO
    {
        $currency = $company->currency->code;
        $zero = Money::zero($currency);

        // Get all WHT entries in the period
        $entries = WithholdingTaxEntry::query()
            ->with(['vendor', 'withholdingTaxType', 'certificate'])
            ->where('company_id', $company->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        // Build vendor summary
        $vendorLines = $this->buildVendorSummary($entries, $currency);

        // Build type summary
        $typeLines = $this->buildTypeSummary($entries, $currency);

        // Calculate totals
        $totalBase = $entries->reduce(
            fn (Money $carry, WithholdingTaxEntry $entry) => $carry->plus($entry->base_amount),
            $zero
        );

        $totalWithheld = $entries->reduce(
            fn (Money $carry, WithholdingTaxEntry $entry) => $carry->plus($entry->withheld_amount),
            $zero
        );

        // Count certificates and uncertified entries
        $certificateCount = WithholdingTaxCertificate::query()
            ->where('company_id', $company->id)
            ->whereBetween('certificate_date', [$startDate, $endDate])
            ->count();

        $uncertifiedCount = $entries->whereNull('withholding_tax_certificate_id')->count();

        return new WithholdingTaxReportDTO(
            vendorLines: $vendorLines,
            typeLines: $typeLines,
            totalBaseAmount: $totalBase,
            totalWithheldAmount: $totalWithheld,
            totalCertificates: $certificateCount,
            uncertifiedEntries: $uncertifiedCount,
        );
    }

    /**
     * @param  Collection<int, WithholdingTaxEntry>  $entries
     * @return Collection<int, WithholdingTaxReportLineDTO>
     */
    private function buildVendorSummary(Collection $entries, string $currency): Collection
    {
        $zero = Money::zero($currency);
        $grouped = $entries->groupBy('vendor_id');

        return $grouped->map(function (Collection $vendorEntries, int $vendorId) use ($zero) {
            $vendor = $vendorEntries->first()->vendor;

            $baseAmount = $vendorEntries->reduce(
                fn (Money $carry, WithholdingTaxEntry $entry) => $carry->plus($entry->base_amount),
                $zero
            );

            $withheldAmount = $vendorEntries->reduce(
                fn (Money $carry, WithholdingTaxEntry $entry) => $carry->plus($entry->withheld_amount),
                $zero
            );

            $certificateIds = $vendorEntries->pluck('withholding_tax_certificate_id')->filter()->unique();

            return new WithholdingTaxReportLineDTO(
                vendorId: $vendorId,
                vendorName: $vendor->name ?? 'Unknown',
                baseAmount: $baseAmount,
                withheldAmount: $withheldAmount,
                entryCount: $vendorEntries->count(),
                certificateCount: $certificateIds->count(),
            );
        })->values();
    }

    /**
     * @param  Collection<int, WithholdingTaxEntry>  $entries
     * @return Collection<int, WithholdingTaxReportTypeLineDTO>
     */
    private function buildTypeSummary(Collection $entries, string $currency): Collection
    {
        $zero = Money::zero($currency);
        $grouped = $entries->groupBy('withholding_tax_type_id');

        return $grouped->map(function (Collection $typeEntries, int $typeId) use ($zero) {
            $type = $typeEntries->first()->withholdingTaxType;

            $baseAmount = $typeEntries->reduce(
                fn (Money $carry, WithholdingTaxEntry $entry) => $carry->plus($entry->base_amount),
                $zero
            );

            $withheldAmount = $typeEntries->reduce(
                fn (Money $carry, WithholdingTaxEntry $entry) => $carry->plus($entry->withheld_amount),
                $zero
            );

            return new WithholdingTaxReportTypeLineDTO(
                typeId: $typeId,
                typeName: $type->name ?? 'Unknown',
                rate: $type->rate ?? 0,
                baseAmount: $baseAmount,
                withheldAmount: $withheldAmount,
                entryCount: $typeEntries->count(),
            );
        })->values();
    }
}
