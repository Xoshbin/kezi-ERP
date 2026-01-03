<?php

namespace Modules\Accounting\DataTransferObjects\Accounting;

use Carbon\Carbon;

readonly class CreateFiscalYearDTO
{
    public function __construct(
        public int $companyId,
        public string $name,
        public Carbon $startDate,
        public Carbon $endDate,
        public bool $generatePeriods = false,
    ) {}
}
