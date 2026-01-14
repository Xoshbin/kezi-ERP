<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\JournalEntry;
use Modules\HR\DataTransferObjects\HumanResources\CreateEmployeeDTO;
use Modules\HR\DataTransferObjects\HumanResources\CreateEmploymentContractDTO;
use Modules\HR\Models\Employee;
use Modules\HR\Models\EmploymentContract;
use Modules\HR\Models\Payroll;
use Modules\HR\Services\HumanResources\EmployeeService;
use Modules\HR\Services\HumanResources\PayrollService;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->currency = $this->company->currency;

    // Create HR-related accounts for the company
    createHRAccounts($this->company, $this->currency);

    // Create user for attribution
    $this->user = \App\Models\User::factory()->create();
    $this->user->companies()->attach($this->company);

    // Create employee and contract
    $data = createEmployeeWithContract($this->company, $this->currency);
    $this->employee = $data['employee'];
    $this->contract = $data['contract'];
});

it('can create employee with employment contract', function () {
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

    expect($employee)->toBeInstanceOf(Employee::class)
        ->first_name->toBe('John')
        ->last_name->toBe('Doe')
        ->employee_number->toBe('EMP002')
        ->is_active->toBeTrue();

    // Verify contract was created
    expect($employee->currentContract)->not->toBeNull()
        ->contract_number->toBe('CON002')
        ->base_salary->isEqualTo(Money::of(1000000, $this->currency->code))->toBeTrue();
});

it('can process payroll with accounting integration', function () {
    $payrollService = app(PayrollService::class);

    $payroll = $payrollService->processPayroll(
        employee: $this->employee,
        periodStartDate: '2024-01-01',
        periodEndDate: '2024-01-31',
        payDate: '2024-01-31',
        user: $this->user
    );

    expect($payroll)->toBeInstanceOf(Payroll::class)
        ->employee_id->toBe($this->employee->id)
        ->period_start_date->format('Y-m-d')->toBe('2024-01-01')
        ->period_end_date->format('Y-m-d')->toBe('2024-01-31')
        ->status->toBe('draft');

    // Verify payroll calculations
    $expectedBaseSalary = Money::of(1000000, $this->currency->code);
    $expectedHousingAllowance = Money::of(200000, $this->currency->code);
    $expectedTransportAllowance = Money::of(100000, $this->currency->code);
    $expectedMealAllowance = Money::of(50000, $this->currency->code);

    expect($payroll->base_salary->isEqualTo($expectedBaseSalary))->toBeTrue();
    expect($payroll->housing_allowance->isEqualTo($expectedHousingAllowance))->toBeTrue();
    expect($payroll->transport_allowance->isEqualTo($expectedTransportAllowance))->toBeTrue();
    expect($payroll->meal_allowance->isEqualTo($expectedMealAllowance))->toBeTrue();

    // Verify gross salary calculation
    $expectedGrossSalary = $expectedBaseSalary
        ->plus($expectedHousingAllowance)
        ->plus($expectedTransportAllowance)
        ->plus($expectedMealAllowance);

    expect($payroll->gross_salary->isEqualTo($expectedGrossSalary))->toBeTrue();

    // Verify deductions were calculated
    expect($payroll->income_tax->isGreaterThan(Money::of(0, $this->currency->code)))->toBeTrue();
    expect($payroll->social_security->isGreaterThan(Money::of(0, $this->currency->code)))->toBeTrue();

    // Verify net salary is less than gross
    expect($payroll->net_salary->isLessThan($payroll->gross_salary))->toBeTrue();

    // Verify payroll lines were created
    expect($payroll->payrollLines->count())->toBeGreaterThan(0);
});

it('creates journal entry when payroll is approved', function () {
    $payrollService = app(PayrollService::class);

    // Process payroll
    $payroll = $payrollService->processPayroll(
        employee: $this->employee,
        periodStartDate: '2024-01-01',
        periodEndDate: '2024-01-31',
        payDate: '2024-01-31',
        user: $this->user
    );

    expect($payroll->status)->toBe('draft')
        ->journal_entry_id->toBeNull();

    // Approve payroll
    $payrollService->approvePayroll($payroll, $this->user);

    $payroll->refresh();

    expect($payroll)
        ->status->toBe('processed')
        ->journal_entry_id->not->toBeNull()
        ->approved_by_user_id->not->toBeNull()
        ->approved_at->not->toBeNull();

    // Verify journal entry was created
    $journalEntry = JournalEntry::find($payroll->journal_entry_id);
    expect($journalEntry)->toBeInstanceOf(JournalEntry::class)
        ->company_id->toBe($this->company->id)
        ->reference->toBe($payroll->payroll_number)
        ->source_type->toBe(Payroll::class)
        ->source_id->toBe($payroll->id)
        ->is_posted->toBeTrue();

    // Verify journal entry lines
    expect($journalEntry->lines->count())->toBeGreaterThan(0);

    // Verify debits equal credits
    $totalDebits = $journalEntry->lines->sum(fn ($line) => (int) $line->debit->getMinorAmount()->toInt());
    $totalCredits = $journalEntry->lines->sum(fn ($line) => (int) $line->credit->getMinorAmount()->toInt());
    expect($totalDebits)->toBe($totalCredits);
});

// Helper functions for setup
function createHRAccounts($company, $currency)
{
    // Create HR-related accounts
    $salaryPayableAccount = Account::factory()->create([
        'company_id' => $company->id,
        'code' => '2100',
        'name' => 'Salary Payable',
        'type' => 'current_liabilities',
    ]);

    $salaryExpenseAccount = Account::factory()->create([
        'company_id' => $company->id,
        'code' => '6100',
        'name' => 'Salary Expense',
        'type' => 'expense',
    ]);

    $incomeTaxPayableAccount = Account::factory()->create([
        'company_id' => $company->id,
        'code' => '2110',
        'name' => 'Income Tax Payable',
        'type' => 'current_liabilities',
    ]);

    $socialSecurityPayableAccount = Account::factory()->create([
        'company_id' => $company->id,
        'code' => '2120',
        'name' => 'Social Security Payable',
        'type' => 'current_liabilities',
    ]);

    $healthInsurancePayableAccount = Account::factory()->create([
        'company_id' => $company->id,
        'code' => '2130',
        'name' => 'Health Insurance Payable',
        'type' => 'current_liabilities',
    ]);

    $pensionPayableAccount = Account::factory()->create([
        'company_id' => $company->id,
        'code' => '2140',
        'name' => 'Pension Payable',
        'type' => 'current_liabilities',
    ]);

    $payrollJournal = Journal::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'name' => 'Payroll Journal',
        'short_code' => 'PAY',
        'type' => 'miscellaneous',
    ]);

    // Update company with HR default accounts
    $company->update([
        'default_salary_payable_account_id' => $salaryPayableAccount->id,
        'default_salary_expense_account_id' => $salaryExpenseAccount->id,
        'default_payroll_journal_id' => $payrollJournal->id,
        'default_income_tax_payable_account_id' => $incomeTaxPayableAccount->id,
        'default_social_security_payable_account_id' => $socialSecurityPayableAccount->id,
        'default_health_insurance_payable_account_id' => $healthInsurancePayableAccount->id,
        'default_pension_payable_account_id' => $pensionPayableAccount->id,
    ]);
}

function createEmployeeWithContract($company, $currency)
{
    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'employee_number' => 'EMP001',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@example.com',
        'hire_date' => '2024-01-01',
        'employment_status' => 'active',
        'is_active' => true,
    ]);

    $contract = EmploymentContract::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'currency_id' => $currency->id,
        'contract_number' => 'CON001',
        'contract_type' => 'permanent',
        'start_date' => '2024-01-01',
        'is_active' => true,
        'base_salary' => Money::of(1000000, $currency->code),
        'housing_allowance' => Money::of(200000, $currency->code),
        'transport_allowance' => Money::of(100000, $currency->code),
        'meal_allowance' => Money::of(50000, $currency->code),
        'other_allowances' => Money::of(0, $currency->code),
        'pay_frequency' => 'monthly',
        'working_hours_per_week' => 40.0,
        'working_days_per_week' => 5.0,
        'annual_leave_days' => 30,
        'sick_leave_days' => 15,
    ]);

    return ['employee' => $employee, 'contract' => $contract];
}
