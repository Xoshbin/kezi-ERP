<?php

namespace App\Services\Payments\Strategies;

use App\DataTransferObjects\Payments\CreatePaymentDTO;
use App\DataTransferObjects\Payments\UpdatePaymentDTO;
use App\Models\Payment;

class PayrollPaymentStrategy implements PaymentStrategy
{
    /**
     * Execute the strategy for creating a payroll payment.
     * This handles payments for employee salaries.
     */
    public function executeCreate(Payment $payment, CreatePaymentDTO $dto): void
    {
        // For payroll payments, the main logic is handled during journal entry creation.
        // The counterpart_account_id should already be set to the salary payable account.
        // This strategy ensures the payment is properly configured for payroll payments.
        
        // Validation that counterpart_account_id exists should be in the Action or Form Request.
        // The strategy assumes the payment is already properly configured.
    }

    /**
     * Execute the strategy for updating a payroll payment.
     * This handles updating payroll payments.
     */
    public function executeUpdate(Payment $payment, UpdatePaymentDTO $dto): void
    {
        // For payroll payments, the main logic is handled during journal entry creation.
        // The counterpart_account_id should already be updated on the payment.
        // This strategy ensures the payment is properly configured for payroll payments.
    }
}
