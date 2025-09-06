<?php

namespace App\Actions\HumanResources;

use App\Actions\Payments\CreatePaymentAction;
use App\DataTransferObjects\Payments\CreatePaymentDTO;
use App\Enums\Payments\PaymentMethod;
use App\Enums\Payments\PaymentType;
use App\Models\Journal;
use App\Models\Payment;
use App\Models\Payroll;
use App\Models\User;
use InvalidArgumentException;

class CreatePaymentFromPayrollAction
{
    public function __construct(
        private readonly CreatePaymentAction $createPaymentAction
    ) {}

    /**
     * Create a payment from an approved payroll.
     *
     * @throws InvalidArgumentException
     */
    public function execute(Payroll $payroll, User $user): Payment
    {
        // Validate payroll status
        if ($payroll->status !== 'processed') {
            throw new InvalidArgumentException('Only processed payrolls can be paid.');
        }

        if ($payroll->payment_id) {
            throw new InvalidArgumentException('Payroll has already been paid.');
        }

        // Get company's default bank journal for payments
        $company = $payroll->company;
        /** @var Journal|null $bankJournal */
        $bankJournal = $company->defaultBankJournal;

        if (! $bankJournal instanceof Journal) {
            throw new InvalidArgumentException('No default bank journal found for company.');
        }

        // Get salary payable account from company defaults
        $salaryPayableAccountId = $company->default_salary_payable_account_id;
        if (! $salaryPayableAccountId) {
            throw new InvalidArgumentException('No default salary payable account configured for company.');
        }

        // For now, we'll use the employee's name as the partner reference
        // In a future enhancement, we could create actual partner records for employees
        $reference = "Salary payment for {$payroll->employee->first_name} {$payroll->employee->last_name} - {$payroll->payroll_number}";

        // Create payment DTO
        $createPaymentDTO = new CreatePaymentDTO(
            company_id: $payroll->company_id,
            journal_id: $bankJournal->getKey(),
            currency_id: $payroll->currency_id,
            payment_date: $payroll->pay_date->format('Y-m-d'),
            payment_type: PaymentType::Outbound,
            payment_method: PaymentMethod::BankTransfer, // Default for payroll
            partner_id: null, // For now, no partner relationship
            amount: $payroll->net_salary,
            document_links: [], // No document links for payroll payments
            reference: $reference
        );

        // Create the payment
        return $this->createPaymentAction->execute($createPaymentDTO, $user);
    }
}
