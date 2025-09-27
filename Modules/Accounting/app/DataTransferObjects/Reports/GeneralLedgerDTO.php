<?php

namespace Modules\Accounting\DataTransferObjects\Reports;

use Illuminate\Support\Collection;

readonly class GeneralLedgerDTO
{
    /**
     * @param  Collection<int, GeneralLedgerAccountDTO>  $accounts
     */
    public function __construct(
        public Collection $accounts,
    ) {}
}
