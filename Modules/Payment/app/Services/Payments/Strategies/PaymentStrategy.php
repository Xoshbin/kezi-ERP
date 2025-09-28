<?php

namespace Modules\Payment\Services\Payments\Strategies;

use Modules\Payment\Models\Payment;
use Modules\Payment\DataTransferObjects\Payments\CreatePaymentDTO;
use Modules\Payment\DataTransferObjects\Payments\UpdatePaymentDTO;

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
