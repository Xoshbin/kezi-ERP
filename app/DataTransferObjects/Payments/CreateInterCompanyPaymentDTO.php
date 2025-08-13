<?php

namespace App\DataTransferObjects\Payments;

readonly class CreateInterCompanyPaymentDTO
{
    /**
     * @param VendorBillPaymentDTO[] $vendor_bill_payments
     */
    public function __construct(
        public int $paying_company_id,
        public int $beneficiary_company_id,
        public int $journal_id,
        public int $currency_id,
        public string $payment_date,
        public array $vendor_bill_payments,
        public ?string $reference = null,
        public ?string $notes = null,
    ) {}
}
