<?php

namespace Kezi\HR\Actions\HumanResources;

use App\Models\User;
use InvalidArgumentException;
use Kezi\Accounting\Models\Journal;
use Kezi\Foundation\Models\Partner;
use Kezi\HR\Models\Employee;
use Kezi\HR\Models\Payroll;
use Kezi\Payment\Actions\Payments\CreatePaymentAction;
use Kezi\Payment\DataTransferObjects\Payments\CreatePaymentDTO;
use Kezi\Payment\Enums\Payments\PaymentMethod;
use Kezi\Payment\Enums\Payments\PaymentType;
use Kezi\Payment\Models\Payment;

class CreatePaymentFromPayrollAction
{
    public function __construct(
        private readonly CreatePaymentAction $createPaymentAction,
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
            paid_to_from_partner_id: $this->getOrCreatePartnerForEmployee($payroll->employee)->id,
            amount: $payroll->net_salary,
            document_links: [], // No document links for payroll payments
            reference: $reference
        );

        // Create the payment
        $payment = $this->createPaymentAction->execute($createPaymentDTO, $user);

        // Update Payroll status and link payment
        $payroll->update([
            'payment_id' => $payment->id,
            'status' => 'paid',
        ]);

        return $payment;
    }

    private function getOrCreatePartnerForEmployee(Employee $employee): Partner
    {
        // Check if a partner already exists for this employee
        // This assumes we might have a way to link them, e.g. via email or a custom field.
        // For now, we'll search by email.
        $partner = Partner::where('email', $employee->email)
            ->where('company_id', $employee->company_id)
            ->first();

        if ($partner) {
            return $partner;
        }

        // Create a new partner for the employee
        return Partner::create([
            'company_id' => $employee->company_id,
            'name' => $employee->first_name.' '.$employee->last_name,
            'email' => $employee->email,
            'phone' => $employee->phone,
            'type' => 'vendor',
        ]);
    }
}
