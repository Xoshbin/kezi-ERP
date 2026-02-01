<?php

namespace Jmeryar\Accounting\DataTransferObjects\Accounting;

use Jmeryar\Accounting\Enums\Accounting\WithholdingTaxApplicability;

class CreateWithholdingTaxTypeDTO
{
    public function __construct(
        public readonly int $company_id,
        public readonly array $name, // Translatable array ['en' => 'Name', 'ar' => 'الاسم']
        public readonly float $rate, // As decimal, e.g., 0.05 for 5%
        public readonly int $withholding_account_id,
        public readonly WithholdingTaxApplicability $applicable_to,
        public readonly ?int $threshold_amount = null, // Minor units
        public readonly bool $is_active = true,
    ) {}
}
