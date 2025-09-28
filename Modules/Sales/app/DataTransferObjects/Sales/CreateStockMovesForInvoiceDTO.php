<?php

namespace Modules\Sales\DataTransferObjects\Sales;

use App\Models\User;
use Modules\Sales\Models\Invoice;


readonly class CreateStockMovesForInvoiceDTO
{
    public function __construct(
        public Invoice $invoice,
        public User $user,
    ) {}
}
