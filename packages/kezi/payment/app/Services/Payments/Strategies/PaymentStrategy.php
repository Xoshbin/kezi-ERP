<?php

namespace Kezi\Payment\Services\Payments\Strategies;

use Kezi\Payment\DataTransferObjects\Payments\CreatePaymentDTO;
use Kezi\Payment\DataTransferObjects\Payments\UpdatePaymentDTO;
use Kezi\Payment\Models\Payment;

interface PaymentStrategy
{
    /**
     * Execute the strategy for creating a payment.
     */
    public function executeCreate(Payment $payment, CreatePaymentDTO $dto): void;

    /**
     * Execute the strategy for updating a payment.
     */
    public function executeUpdate(Payment $payment, UpdatePaymentDTO $dto): void;
}
