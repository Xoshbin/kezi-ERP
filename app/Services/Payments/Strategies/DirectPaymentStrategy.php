<?php

namespace App\Services\Payments\Strategies;

use App\DataTransferObjects\Payments\CreatePaymentDTO;
use App\DataTransferObjects\Payments\UpdatePaymentDTO;
use App\Models\Payment;

class DirectPaymentStrategy implements PaymentStrategy
{
    /**
     * Execute the strategy for creating a direct payment.
     * This handles non-settlement payments like loans, capital injections, etc.
     */
    public function executeCreate(Payment $payment, CreatePaymentDTO $dto): void
    {
        // For direct payments, the main logic is handled during journal entry creation.
        // The counterpart_account_id should already be set on the payment.
        // This strategy ensures the payment is properly configured for direct payments.
        
        // Validation that counterpart_account_id exists should be in the Action or Form Request.
        // The strategy assumes the payment is already properly configured.
    }

    /**
     * Execute the strategy for updating a direct payment.
     * This handles updating non-settlement payments.
     */
    public function executeUpdate(Payment $payment, UpdatePaymentDTO $dto): void
    {
        // For direct payments, the main logic is handled during journal entry creation.
        // The counterpart_account_id should already be updated on the payment.
        // This strategy ensures the payment is properly configured for direct payments.
    }
}
