<?php

namespace Kezi\Accounting\Actions\Accounting;

use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use Kezi\Accounting\DataTransferObjects\Accounting\ApplyWithholdingTaxDTO;
use Kezi\Accounting\Models\WithholdingTaxEntry;
use Kezi\Accounting\Models\WithholdingTaxType;

class ApplyWithholdingTaxAction
{
    /**
     * Apply withholding tax to a payment and create the WHT entry.
     *
     * @return WithholdingTaxEntry|null Returns null if threshold not met
     */
    public function execute(ApplyWithholdingTaxDTO $dto): ?WithholdingTaxEntry
    {
        return DB::transaction(function () use ($dto) {
            $whtType = WithholdingTaxType::findOrFail($dto->withholding_tax_type_id);

            // Check threshold - if base amount is below threshold, no WHT applies
            if ($whtType->threshold_amount !== null) {
                if ($dto->base_amount->isLessThan($whtType->threshold_amount)) {
                    return null;
                }
            }

            // Calculate the withholding amount
            $withheldAmount = $whtType->calculateWithholding($dto->base_amount);

            // If withheld amount is zero or negative, skip
            if ($withheldAmount->isZero() || $withheldAmount->isNegative()) {
                return null;
            }

            // Create the WHT entry record
            return WithholdingTaxEntry::create([
                'company_id' => $dto->company_id,
                'payment_id' => $dto->payment_id,
                'withholding_tax_type_id' => $dto->withholding_tax_type_id,
                'vendor_id' => $dto->vendor_id,
                'base_amount' => $dto->base_amount,
                'withheld_amount' => $withheldAmount,
                'rate_applied' => $whtType->rate,
                'currency_id' => $dto->currency_id,
            ]);
        });
    }

    /**
     * Calculate withholding tax without creating an entry (for preview/estimation).
     */
    public function calculateWithholding(int $withholdingTaxTypeId, Money $baseAmount): Money
    {
        $whtType = WithholdingTaxType::findOrFail($withholdingTaxTypeId);

        return $whtType->calculateWithholding($baseAmount);
    }
}
