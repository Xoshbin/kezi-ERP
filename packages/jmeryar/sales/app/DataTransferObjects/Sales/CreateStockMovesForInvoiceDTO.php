<?php

namespace Jmeryar\Sales\DataTransferObjects\Sales;

use App\Models\User;
use Jmeryar\Sales\Models\Invoice;

readonly class CreateStockMovesForInvoiceDTO
{
    public function __construct(
        public Invoice $invoice,
        public User $user,
    ) {}
}
