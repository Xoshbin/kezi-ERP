<?php

namespace Kezi\Accounting\DataTransferObjects\Accounting;

use Kezi\Accounting\Models\FiscalYear;

readonly class CloseFiscalYearDTO
{
    public function __construct(
        public FiscalYear $fiscalYear,
        public int $retainedEarningsAccountId,
        public int $closedByUserId,
        public ?string $description = null,
    ) {}
}
