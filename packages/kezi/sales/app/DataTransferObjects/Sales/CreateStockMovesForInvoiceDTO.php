<?php

namespace Kezi\Sales\DataTransferObjects\Sales;

use App\Models\User;
use Kezi\Sales\Models\Invoice;

readonly class CreateStockMovesForInvoiceDTO
{
    public function __construct(
        public Invoice $invoice,
        public User $user,
    ) {}
}
