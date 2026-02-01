<?php

namespace Jmeryar\Accounting\DataTransferObjects\Accounting;

use Jmeryar\Accounting\Models\FiscalYear;

readonly class CloseFiscalYearDTO
{
    public function __construct(
        public FiscalYear $fiscalYear,
        public int $retainedEarningsAccountId,
        public int $closedByUserId,
        public ?string $description = null,
    ) {}
}
