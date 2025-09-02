<?php

namespace Tests\Feature\HumanResources;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmploymentContract;
use App\Models\Payroll;
use App\Models\Payment;
use App\Models\Journal;
use App\Models\Account;
use App\Models\Currency;
use App\Services\HumanResources\PayrollService;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentType;
use App\Enums\Payments\PaymentStatus;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

class PayrollPaymentTest extends TestCase
{
    use RefreshDatabase, WithConfiguredCompany;

    private User $user;
    private Company $company;
    private Employee $employee;
    private EmploymentContract $contract;
    private PayrollService $payrollService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupWithConfiguredCompany();
        $this->user = $this->company->users()->first();
        $this->payrollService = app(PayrollService::class);

        // Create employee with contract
        $this->employee = Employee::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->contract = EmploymentContract::factory()->create([
            'employee_id' => $this->employee->id,
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'base_salary' => Money::of(100000, 'IQD'), // 100,000 IQD
            'is_active' => true,
        ]);

        // Setup HR accounts for the company
        $this->setupHRAccounts();
    }

    private function setupHRAccounts(): void
    {
        $salaryExpenseAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Salary Expense',
            'code' => '6100',
            'type' => 'expense',
        ]);

        $salaryPayableAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Salary Payable',
            'code' => '2100',
            'type' => 'current_liabilities',
        ]);

        $bankJournal = Journal::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Bank Journal',
            'type' => 'bank',
        ]);

        $this->company->update([
            'default_salary_expense_account_id' => $salaryExpenseAccount->id,
            'default_salary_payable_account_id' => $salaryPayableAccount->id,
            'default_bank_journal_id' => $bankJournal->id,
        ]);
    }

    /** @test */
    public function it_can_create_payment_from_processed_payroll()
    {
        // Create and process payroll
        $payroll = $this->payrollService->processPayroll(
            employee: $this->employee,
            periodStartDate: '2024-01-01',
            periodEndDate: '2024-01-31',
            payDate: '2024-01-31',
            user: $this->user
        );

        // Approve payroll
        $this->payrollService->approvePayroll($payroll, $this->user);

        $this->assertEquals('processed', $payroll->fresh()->status);
        $this->assertNotNull($payroll->fresh()->journal_entry_id);

        // Create payment from payroll
        $payment = $this->payrollService->payEmployee($payroll, $this->user);

        // Assert payment was created correctly
        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals($payroll->company_id, $payment->company_id);
        $this->assertEquals($payroll->currency_id, $payment->currency_id);
        $this->assertEquals($payroll->pay_date->format('Y-m-d'), $payment->payment_date->format('Y-m-d'));
        $this->assertEquals(PaymentPurpose::Payroll, $payment->payment_purpose);
        $this->assertEquals(PaymentType::Outbound, $payment->payment_type);
        $this->assertEquals(PaymentStatus::Draft, $payment->status);
        $this->assertTrue($payroll->net_salary->isEqualTo($payment->amount));

        // Assert payroll was updated
        $payroll->refresh();
        $this->assertEquals('paid', $payroll->status);
        $this->assertEquals($payment->id, $payroll->payment_id);

        // Assert payment references salary payable account
        $this->assertEquals($this->company->default_salary_payable_account_id, $payment->counterpart_account_id);

        // Assert payment reference contains employee info
        $this->assertStringContainsString($this->employee->first_name, $payment->reference);
        $this->assertStringContainsString($this->employee->last_name, $payment->reference);
        $this->assertStringContainsString($payroll->payroll_number, $payment->reference);
    }

    /** @test */
    public function it_cannot_create_payment_from_draft_payroll()
    {
        // Create draft payroll
        $payroll = $this->payrollService->processPayroll(
            employee: $this->employee,
            periodStartDate: '2024-01-01',
            periodEndDate: '2024-01-31',
            payDate: '2024-01-31',
            user: $this->user
        );

        $this->assertEquals('draft', $payroll->status);

        // Attempt to create payment should fail
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only processed payrolls can be paid.');

        $this->payrollService->payEmployee($payroll, $this->user);
    }

    /** @test */
    public function it_cannot_create_payment_for_already_paid_payroll()
    {
        // Create, approve, and pay payroll
        $payroll = $this->payrollService->processPayroll(
            employee: $this->employee,
            periodStartDate: '2024-01-01',
            periodEndDate: '2024-01-31',
            payDate: '2024-01-31',
            user: $this->user
        );

        $this->payrollService->approvePayroll($payroll, $this->user);
        $payroll->refresh(); // Refresh to get updated status

        $this->payrollService->payEmployee($payroll, $this->user);
        $payroll->refresh(); // Refresh to get updated status and payment_id

        // Attempt to pay again should fail
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Payroll has already been paid.');

        $this->payrollService->payEmployee($payroll, $this->user);
    }

    /** @test */
    public function it_validates_company_has_required_accounts_and_journals()
    {
        // Remove required accounts
        $this->company->update([
            'default_salary_payable_account_id' => null,
        ]);

        // Create and approve payroll
        $payroll = $this->payrollService->processPayroll(
            employee: $this->employee,
            periodStartDate: '2024-01-01',
            periodEndDate: '2024-01-31',
            payDate: '2024-01-31',
            user: $this->user
        );

        $this->payrollService->approvePayroll($payroll, $this->user);

        // Attempt to create payment should fail
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No default salary payable account configured for company.');

        $this->payrollService->payEmployee($payroll, $this->user);
    }

    /** @test */
    public function payroll_helper_methods_work_correctly()
    {
        // Create processed payroll
        $payroll = $this->payrollService->processPayroll(
            employee: $this->employee,
            periodStartDate: '2024-01-01',
            periodEndDate: '2024-01-31',
            payDate: '2024-01-31',
            user: $this->user
        );

        $this->payrollService->approvePayroll($payroll, $this->user);

        // Test canBePaid method
        $this->assertTrue($payroll->canBePaid());
        $this->assertFalse($payroll->isPaid());

        // Pay the payroll
        $this->payrollService->payEmployee($payroll, $this->user);
        $payroll->refresh();

        // Test after payment
        $this->assertFalse($payroll->canBePaid());
        $this->assertTrue($payroll->isPaid());

        // Test employee full name attribute
        $expectedName = $this->employee->first_name . ' ' . $this->employee->last_name;
        $this->assertEquals($expectedName, $payroll->employee_full_name);
    }
}
