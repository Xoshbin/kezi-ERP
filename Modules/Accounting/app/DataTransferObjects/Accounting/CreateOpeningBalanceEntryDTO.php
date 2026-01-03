<?php

namespace Modules\Accounting\DataTransferObjects\Accounting;

use Modules\Accounting\Models\FiscalYear;

readonly class CreateOpeningBalanceEntryDTO
{
    public function __construct(
        public FiscalYear $newFiscalYear,
        public FiscalYear $previousFiscalYear,
        public int $createdByUserId,
        public ?string $description = null,
    ) {}
}
