<?php

namespace Jmeryar\Accounting\Actions\Accounting;

use App\Models\Company;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Jmeryar\Accounting\DataTransferObjects\Accounting\CreateWithholdingTaxCertificateDTO;
use Jmeryar\Accounting\Enums\Accounting\WithholdingTaxCertificateStatus;
use Jmeryar\Accounting\Models\WithholdingTaxCertificate;
use Jmeryar\Accounting\Models\WithholdingTaxEntry;
use Jmeryar\Foundation\Models\Sequence;

class CreateWithholdingTaxCertificateAction
{
    public function execute(CreateWithholdingTaxCertificateDTO $dto): WithholdingTaxCertificate
    {
        return DB::transaction(function () use ($dto) {
            // Validate that we have entries to certify
            if (empty($dto->entry_ids)) {
                throw new InvalidArgumentException('At least one withholding tax entry must be selected for the certificate.');
            }

            // Fetch the entries and validate they are uncertified
            $entries = WithholdingTaxEntry::whereIn('id', $dto->entry_ids)
                ->where('company_id', $dto->company_id)
                ->where('vendor_id', $dto->vendor_id)
                ->whereNull('withholding_tax_certificate_id')
                ->get();

            if ($entries->count() !== count($dto->entry_ids)) {
                throw new InvalidArgumentException('Some entries are already certified or do not belong to the specified vendor.');
            }

            // Verify all entries have the same currency
            $currencyIds = $entries->pluck('currency_id')->unique();
            if ($currencyIds->count() > 1) {
                throw new InvalidArgumentException('All entries must have the same currency.');
            }

            // Calculate totals from entries
            $company = Company::findOrFail($dto->company_id);
            $currencyCode = $company->currency->code;
            $totalBase = Money::zero($currencyCode);
            $totalWithheld = Money::zero($currencyCode);

            foreach ($entries as $entry) {
                $totalBase = $totalBase->plus($entry->base_amount);
                $totalWithheld = $totalWithheld->plus($entry->withheld_amount);
            }

            // Generate certificate number using Sequence
            $sequence = Sequence::getOrCreateSequence($dto->company_id, 'withholding_tax_certificate', 'WHT');
            $certificateNumber = $sequence->getNextNumber();

            // Create the certificate
            $certificate = WithholdingTaxCertificate::create([
                'company_id' => $dto->company_id,
                'certificate_number' => $certificateNumber,
                'vendor_id' => $dto->vendor_id,
                'certificate_date' => $dto->certificate_date,
                'period_start' => $dto->period_start,
                'period_end' => $dto->period_end,
                'total_base_amount' => $totalBase,
                'total_withheld_amount' => $totalWithheld,
                'currency_id' => $dto->currency_id,
                'status' => WithholdingTaxCertificateStatus::Draft,
                'notes' => $dto->notes,
            ]);

            // Link all entries to this certificate
            WithholdingTaxEntry::whereIn('id', $dto->entry_ids)
                ->update(['withholding_tax_certificate_id' => $certificate->id]);

            return $certificate->refresh();
        });
    }
}
