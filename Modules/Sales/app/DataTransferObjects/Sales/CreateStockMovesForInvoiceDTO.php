<?php

namespace Modules\Sales\DataTransferObjects\Sales;

use App\Models\User;

readonly class CreateStockMovesForInvoiceDTO
{
    public function __construct(
        public \Modules\Sales\Models\Invoice $invoice,
        public User $user,
    ) {}
}
