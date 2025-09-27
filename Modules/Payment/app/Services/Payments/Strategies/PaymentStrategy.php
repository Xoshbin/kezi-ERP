<?php

namespace Modules\Payment\Services\Payments\Strategies;

use App\DataTransferObjects\Payments\CreatePaymentDTO;
use App\DataTransferObjects\Payments\UpdatePaymentDTO;

interface PaymentStrategy
{
    /**
     * Execute the strategy for creating a payment.
     */
    public function executeCreate(\Modules\Payment\Models\Payment $payment, CreatePaymentDTO $dto): void;

    /**
     * Execute the strategy for updating a payment.
     */
    public function executeUpdate(\Modules\Payment\Models\Payment $payment, UpdatePaymentDTO $dto): void;
}
