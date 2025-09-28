<?php

namespace Modules\HR\Tests\Feature\HumanResources;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Models\Account;
use Modules\Foundation\Models\Currency;

use Modules\HR\Models\Employee;
use Modules\HR\Models\Payroll;

use Tests\TestCase;
use Tests\Traits\WithConfiguredCompany;

class PayrollProcessingTest extends TestCase
{
    use RefreshDatabase;
    use WithConfiguredCompany;

    private Company $company;

    private Currency $currency;

    private User $user;

    private Employee $employee;

    private EmploymentContract $contract;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupWithConfiguredCompany();
        $this->currency = $this->company->currency;

        // Create HR-related accounts for the company
        $this->createHRAccounts();

        // Create employee and contract
        $this->createEmployeeWithContract();
    }

    /** @test */
    public function it_can_create_employee_with_employment_contract()
    {
        $createEmployeeDTO = new CreateEmployeeDTO(
            company_id: $this->company->id,
            user_id: null,
            department_id: null,
            position_id: null,
            manager_id: null,
            employee_number: 'EMP002',
            first_name: 'John',
            last_name: 'Doe',
            email: 'john.doe.test@example.com',
            phone: '+964-123-456-789',
            date_of_birth: '1990-01-01',
            gender: 'male',
            marital_status: 'single',
            nationality: 'Iraqi',
            national_id: '123456789',
            passport_number: null,
            address_line_1: '123 Main St',
            address_line_2: null,
            city: 'Baghdad',
            state: 'Baghdad',
            zip_code: '10001',
            country: 'Iraq',
            emergency_contact_name: 'Jane Doe',
            emergency_contact_phone: '+964-987-654-321',
            emergency_contact_relationship: 'Sister',
            hire_date: '2024-01-01',
            termination_date: null,
            employment_status: 'active',
            employee_type: 'full_time',
            bank_name: 'Iraqi Bank',
            bank_account_number: '1234567890',
            bank_routing_number: '001',
            is_active: true,
            created_by_user_id: $this->user->id,
        );

        $createContractDTO = new CreateEmploymentContractDTO(
            company_id: $this->company->id,
            employee_id: 0, // Will be set by the service
            currency_id: $this->currency->id,
            contract_number: 'CON002',
            contract_type: 'permanent',
            start_date: '2024-01-01',
            end_date: null,
            is_active: true,
            base_salary: Money::of(1000000, $this->currency->code), // 1,000,000 IQD
            hourly_rate: null,
            pay_frequency: 'monthly',
            housing_allowance: Money::of(200000, $this->currency->code),
            transport_allowance: Money::of(100000, $this->currency->code),
            meal_allowance: Money::of(50000, $this->currency->code),
            other_allowances: Money::of(0, $this->currency->code),
            working_hours_per_week: 40.0,
            working_days_per_week: 5.0,
            annual_leave_days: 30,
            sick_leave_days: 15,
            maternity_leave_days: 90,
            paternity_leave_days: 7,
            probation_period_months: 3,
            probation_end_date: null,
            notice_period_days: 30,
            terms_and_conditions: 'Standard employment terms',
            job_description: 'Software Developer',
            created_by_user_id: $this->user->id,
        );

        $employeeService = app(EmployeeService::class);
        $employee = $employeeService->createEmployee($createEmployeeDTO, $createContractDTO);

        $this->assertInstanceOf(Employee::class, $employee);
        $this->assertEquals('John', $employee->first_name);
        $this->assertEquals('Doe', $employee->last_name);
        $this->assertEquals('EMP002', $employee->employee_number);
        $this->assertTrue($employee->is_active);

        // Verify contract was created
        $this->assertNotNull($employee->currentContract);
        $this->assertEquals('CON002', $employee->currentContract->contract_number);
        $this->assertTrue($employee->currentContract->base_salary->isEqualTo(Money::of(1000000, $this->currency->code)));
    }

    /** @test */
    public function it_can_process_payroll_with_accounting_integration()
    {
        $payrollService = app(PayrollService::class);

        $payroll = $payrollService->processPayroll(
            employee: $this->employee,
            periodStartDate: '2024-01-01',
            periodEndDate: '2024-01-31',
            payDate: '2024-01-31',
            user: $this->user
        );

        $this->assertInstanceOf(Payroll::class, $payroll);
        $this->assertEquals($this->employee->id, $payroll->employee_id);
        $this->assertEquals('2024-01-01', $payroll->period_start_date->format('Y-m-d'));
        $this->assertEquals('2024-01-31', $payroll->period_end_date->format('Y-m-d'));
        $this->assertEquals('draft', $payroll->status);

        // Verify payroll calculations
        $expectedBaseSalary = Money::of(1000000, $this->currency->code);
        $expectedHousingAllowance = Money::of(200000, $this->currency->code);
        $expectedTransportAllowance = Money::of(100000, $this->currency->code);
        $expectedMealAllowance = Money::of(50000, $this->currency->code);

        $this->assertTrue($payroll->base_salary->isEqualTo($expectedBaseSalary));
        $this->assertTrue($payroll->housing_allowance->isEqualTo($expectedHousingAllowance));
        $this->assertTrue($payroll->transport_allowance->isEqualTo($expectedTransportAllowance));
        $this->assertTrue($payroll->meal_allowance->isEqualTo($expectedMealAllowance));

        // Verify gross salary calculation
        $expectedGrossSalary = $expectedBaseSalary
            ->plus($expectedHousingAllowance)
            ->plus($expectedTransportAllowance)
            ->plus($expectedMealAllowance);

        $this->assertTrue($payroll->gross_salary->isEqualTo($expectedGrossSalary));

        // Verify deductions were calculated
        $this->assertTrue($payroll->income_tax->isGreaterThan(Money::of(0, $this->currency->code)));
        $this->assertTrue($payroll->social_security->isGreaterThan(Money::of(0, $this->currency->code)));

        // Verify net salary is less than gross
        $this->assertTrue($payroll->net_salary->isLessThan($payroll->gross_salary));

        // Verify payroll lines were created
        $this->assertGreaterThan(0, $payroll->payrollLines->count());
    }

    /** @test */
    public function it_creates_journal_entry_when_payroll_is_approved()
    {
        $payrollService = app(PayrollService::class);

        // Process payroll
        $payroll = $payrollService->processPayroll(
            employee: $this->employee,
            periodStartDate: '2024-01-01',
            periodEndDate: '2024-01-31',
            payDate: '2024-01-31',
            user: $this->user
        );

        $this->assertEquals('draft', $payroll->status);
        $this->assertNull($payroll->journal_entry_id);

        // Approve payroll
        $payrollService->approvePayroll($payroll, $this->user);

        $payroll->refresh();
        $this->assertEquals('processed', $payroll->status);
        $this->assertNotNull($payroll->journal_entry_id);
        $this->assertNotNull($payroll->approved_by_user_id);
        $this->assertNotNull($payroll->approved_at);

        // Verify journal entry was created
        $journalEntry = JournalEntry::find($payroll->journal_entry_id);
        $this->assertInstanceOf(JournalEntry::class, $journalEntry);
        $this->assertEquals($this->company->id, $journalEntry->company_id);
        $this->assertEquals($payroll->payroll_number, $journalEntry->reference);
        $this->assertEquals(Payroll::class, $journalEntry->source_type);
        $this->assertEquals($payroll->id, $journalEntry->source_id);
        $this->assertTrue($journalEntry->is_posted);

        // Verify journal entry lines
        $this->assertGreaterThan(0, $journalEntry->lines->count());

        // Verify debits equal credits
        $totalDebits = $journalEntry->lines->sum(fn($line) => (int) $line->debit->getMinorAmount()->toInt());
        $totalCredits = $journalEntry->lines->sum(fn($line) => (int) $line->credit->getMinorAmount()->toInt());
        $this->assertEquals($totalDebits, $totalCredits);
    }

    private function createHRAccounts(): void
    {
        // Create HR-related accounts
        $salaryPayableAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'code' => '2100',
            'name' => 'Salary Payable',
            'type' => 'current_liabilities',
        ]);

        $salaryExpenseAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'code' => '6100',
            'name' => 'Salary Expense',
            'type' => 'expense',
        ]);

        $incomeTaxPayableAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'code' => '2110',
            'name' => 'Income Tax Payable',
            'type' => 'current_liabilities',
        ]);

        $socialSecurityPayableAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'code' => '2120',
            'name' => 'Social Security Payable',
            'type' => 'current_liabilities',
        ]);

        $healthInsurancePayableAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'code' => '2130',
            'name' => 'Health Insurance Payable',
            'type' => 'current_liabilities',
        ]);

        $pensionPayableAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'code' => '2140',
            'name' => 'Pension Payable',
            'type' => 'current_liabilities',
        ]);

        $payrollJournal = Journal::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->currency->id,
            'name' => 'Payroll Journal',
            'short_code' => 'PAY',
            'type' => 'miscellaneous',
        ]);

        // Update company with HR default accounts
        $this->company->update([
            'default_salary_payable_account_id' => $salaryPayableAccount->id,
            'default_salary_expense_account_id' => $salaryExpenseAccount->id,
            'default_payroll_journal_id' => $payrollJournal->id,
            'default_income_tax_payable_account_id' => $incomeTaxPayableAccount->id,
            'default_social_security_payable_account_id' => $socialSecurityPayableAccount->id,
            'default_health_insurance_payable_account_id' => $healthInsurancePayableAccount->id,
            'default_pension_payable_account_id' => $pensionPayableAccount->id,
        ]);
    }

    private function createEmployeeWithContract(): void
    {
        $this->employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'employee_number' => 'EMP001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'hire_date' => '2024-01-01',
            'employment_status' => 'active',
            'is_active' => true,
        ]);

        $this->contract = EmploymentContract::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'currency_id' => $this->currency->id,
            'contract_number' => 'CON001',
            'contract_type' => 'permanent',
            'start_date' => '2024-01-01',
            'is_active' => true,
            'base_salary' => Money::of(1000000, $this->currency->code),
            'housing_allowance' => Money::of(200000, $this->currency->code),
            'transport_allowance' => Money::of(100000, $this->currency->code),
            'meal_allowance' => Money::of(50000, $this->currency->code),
            'other_allowances' => Money::of(0, $this->currency->code),
            'pay_frequency' => 'monthly',
            'working_hours_per_week' => 40.0,
            'working_days_per_week' => 5.0,
            'annual_leave_days' => 30,
            'sick_leave_days' => 15,
        ]);
    }
}
