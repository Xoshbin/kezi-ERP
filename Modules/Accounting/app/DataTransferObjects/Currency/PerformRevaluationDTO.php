<?php

namespace Modules\Accounting\DataTransferObjects\Currency;

use Carbon\Carbon;

/**
 * DTO for performing a currency revaluation.
 */
readonly class PerformRevaluationDTO
{
    /**
     * @param  int  $company_id  The company to perform revaluation for
     * @param  int  $created_by_user_id  The user performing the revaluation
     * @param  Carbon  $revaluation_date  The date as of which to revalue balances
     * @param  string|null  $description  Optional description for the revaluation
     * @param  array<int>  $account_ids  Optional specific accounts to revalue (empty = all eligible)
     * @param  array<int>  $currency_ids  Optional specific currencies to revalue (empty = all foreign)
     * @param  bool  $auto_post  Whether to automatically post the revaluation
     */
    public function __construct(
        public int $company_id,
        public int $created_by_user_id,
        public Carbon $revaluation_date,
        public ?string $description = null,
        public array $account_ids = [],
        public array $currency_ids = [],
        public bool $auto_post = false,
    ) {}
}

