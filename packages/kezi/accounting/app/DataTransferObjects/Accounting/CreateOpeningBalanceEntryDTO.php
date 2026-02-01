<?php

namespace Kezi\Accounting\DataTransferObjects\Accounting;

use Kezi\Accounting\Models\FiscalYear;

readonly class CreateOpeningBalanceEntryDTO
{
    public function __construct(
        public FiscalYear $newFiscalYear,
        public FiscalYear $previousFiscalYear,
        public int $createdByUserId,
        public ?string $description = null,
    ) {}
}
