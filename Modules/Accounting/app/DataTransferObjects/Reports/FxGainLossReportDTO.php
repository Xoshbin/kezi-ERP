<?php

namespace Modules\Accounting\DataTransferObjects\Reports;

use Brick\Money\Money;
use Illuminate\Support\Collection;

/**
 * DTO for the FX Gain/Loss Report.
 */
readonly class FxGainLossReportDTO
{
    /**
     * @param  string  $company_name  The company name
     * @param  string  $base_currency  The base currency code
     * @param  string  $start_date  Report start date
     * @param  string  $end_date  Report end date
     * @param  Collection<int, FxGainLossLineDTO>  $realized_gains_losses  Realized FX gains/losses from settled transactions
     * @param  Collection<int, FxGainLossLineDTO>  $unrealized_gains_losses  Unrealized FX gains/losses from revaluations
     * @param  Money  $total_realized_gain  Total realized gains
     * @param  Money  $total_realized_loss  Total realized losses
     * @param  Money  $net_realized  Net realized gain/loss
     * @param  Money  $total_unrealized_gain  Total unrealized gains
     * @param  Money  $total_unrealized_loss  Total unrealized losses
     * @param  Money  $net_unrealized  Net unrealized gain/loss
     * @param  Money  $total_net_fx_impact  Total net FX impact (realized + unrealized)
     */
    public function __construct(
        public string $company_name,
        public string $base_currency,
        public string $start_date,
        public string $end_date,
        public Collection $realized_gains_losses,
        public Collection $unrealized_gains_losses,
        public Money $total_realized_gain,
        public Money $total_realized_loss,
        public Money $net_realized,
        public Money $total_unrealized_gain,
        public Money $total_unrealized_loss,
        public Money $net_unrealized,
        public Money $total_net_fx_impact,
    ) {}
}
