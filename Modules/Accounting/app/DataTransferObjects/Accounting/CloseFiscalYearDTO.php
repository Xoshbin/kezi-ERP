<?php

namespace Modules\Accounting\DataTransferObjects\Accounting;

use Modules\Accounting\Models\FiscalYear;

readonly class CloseFiscalYearDTO
{
    public function __construct(
        public FiscalYear $fiscalYear,
        public int $retainedEarningsAccountId,
        public int $closedByUserId,
        public ?string $description = null,
    ) {}
}
