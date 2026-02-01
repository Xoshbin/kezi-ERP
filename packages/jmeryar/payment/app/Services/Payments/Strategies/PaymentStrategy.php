<?php

namespace Jmeryar\Payment\Services\Payments\Strategies;

use Jmeryar\Payment\DataTransferObjects\Payments\CreatePaymentDTO;
use Jmeryar\Payment\DataTransferObjects\Payments\UpdatePaymentDTO;
use Jmeryar\Payment\Models\Payment;

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
