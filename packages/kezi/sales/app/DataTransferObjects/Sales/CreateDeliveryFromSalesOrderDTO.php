<?php

namespace Kezi\Sales\DataTransferObjects\Sales;

use App\Models\User;
use Carbon\Carbon;
use Kezi\Sales\Models\SalesOrder;

/**
 * Data Transfer Object for creating delivery orders from a sales order
 */
readonly class CreateDeliveryFromSalesOrderDTO
{
    public function __construct(
        public SalesOrder $salesOrder,
        public User $user,
        public ?Carbon $scheduled_date = null,
        public bool $autoConfirm = false,
    ) {}
}
