<?php

namespace App\DataTransferObjects\HumanResources;

use Brick\Money\Money;

readonly class PayrollLineDTO
{
    public function __construct(
        public int $company_id,
        public int $account_id,
        public string $line_type, // earning, deduction, tax, contribution
        public string $code, // salary, overtime, tax, insurance, etc.
        public array $description, // Translatable description
        public float $quantity,
        public ?string $unit, // hours, days, percentage, fixed
        public string|Money|null $rate, // Rate per unit
        public string|Money $amount, // Final calculated amount
        public ?float $tax_rate,
        public bool $is_taxable,
        public bool $is_statutory,
        public string $debit_credit, // debit or credit for journal entry
        public ?int $analytic_account_id,
        public ?string $notes,
        public ?string $reference,
    ) {
    }
}
