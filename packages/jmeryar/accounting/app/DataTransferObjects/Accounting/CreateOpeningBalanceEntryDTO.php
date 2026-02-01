<?php

namespace Jmeryar\Accounting\DataTransferObjects\Accounting;

use Jmeryar\Accounting\Models\FiscalYear;

readonly class CreateOpeningBalanceEntryDTO
{
    public function __construct(
        public FiscalYear $newFiscalYear,
        public FiscalYear $previousFiscalYear,
        public int $createdByUserId,
        public ?string $description = null,
    ) {}
}
