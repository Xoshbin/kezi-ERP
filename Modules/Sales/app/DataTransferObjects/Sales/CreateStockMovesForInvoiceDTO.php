<?php

namespace App\DataTransferObjects\Sales;

use App\Models\Invoice;
use App\Models\User;

readonly class CreateStockMovesForInvoiceDTO
{
    public function __construct(
        public Invoice $invoice,
        public User $user,
    ) {}
}
