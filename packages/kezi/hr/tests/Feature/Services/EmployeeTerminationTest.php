<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Models\Account;
use Kezi\HR\Models\Employee;
use Kezi\HR\Models\EmploymentContract;
use Kezi\HR\Models\Payroll;
use Kezi\HR\Services\HumanResources\EmployeeService;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->currency = $this->company->currency;

    // Create HR-related accounts for the company (needed for payroll)
    createHRAccountsForTerminationTest($this->company, $this->currency);
});

it('generates final payroll and logs asset check skip on termination', function () {
    // Arrange
    $employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'employment_status' => 'active',
        'is_active' => true,
    ]);

    $contract = EmploymentContract::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $employee->id,
        'currency_id' => $this->currency->id,
        'base_salary' => Money::of(1000, $this->currency->code),
        'start_date' => now()->subMonths(6),
        'is_active' => true,
    ]);

    $service = app(EmployeeService::class);
    $terminationDate = now()->toDateString();
    $reason = 'Resignation';

    // Act
    $service->terminateEmployee($employee, $terminationDate, $reason, $this->user);

    // Assert
    // 1. Employee status updated
    expect($employee->fresh()->employment_status)->toBe('terminated');

    // 2. Final Payroll Created
    $payroll = Payroll::where('employee_id', $employee->id)
        ->latest('period_end_date')
        ->first();

    expect($payroll)->not->toBeNull()
        ->period_end_date->format('Y-m-d')->toBe($terminationDate)
        ->status->toBe('draft')
        ->notes->toContain('Final Settlement');

    // 3. Audit Log for Termination exists
    $this->assertDatabaseHas('audit_logs', [
        'auditable_type' => Employee::class,
        'auditable_id' => $employee->id,
        'event_type' => 'employee_terminated',
    ]);

    // 4. Audit Log for Asset Check Skipped exists
    $this->assertDatabaseHas('audit_logs', [
        'auditable_type' => Employee::class,
        'auditable_id' => $employee->id,
        'event_type' => 'asset_check_skipped',
        'description' => 'Asset return check skipped: No asset assignment feature.',
    ]);
});

it('logs warning when reactivating employee without contract info', function () {
    // Arrange
    $employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'employment_status' => 'terminated',
        'is_active' => false,
        'termination_date' => now()->subDays(10),
    ]);

    $service = app(EmployeeService::class);
    $reactivationDate = now()->toDateString();

    // Act
    $service->reactivateEmployee($employee, $reactivationDate, $this->user);

    // Assert
    expect($employee->fresh()->employment_status)->toBe('active');

    $this->assertDatabaseHas('audit_logs', [
        'auditable_type' => Employee::class,
        'auditable_id' => $employee->id,
        'event_type' => 'contract_review_needed',
        'description' => 'Employee reactivated. Please review or create a new employment contract.',
    ]);
});

it('logs info when transferring employee to review contract', function () {
    // Arrange
    $employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'employment_status' => 'active',
    ]);

    $newDepartment = \Kezi\HR\Models\Department::factory()->create(['company_id' => $this->company->id]);
    $service = app(EmployeeService::class);
    $effectiveDate = now()->toDateString();

    // Act
    $service->transferEmployee($employee, $newDepartment->id, null, null, $effectiveDate, $this->user);

    // Assert
    $this->assertDatabaseHas('audit_logs', [
        'auditable_type' => Employee::class,
        'auditable_id' => $employee->id,
        'event_type' => 'contract_review_needed',
        'description' => 'Employee transferred. Please review Employment Contract for potential role/salary updates.',
    ]);
});

// Helper function to setup accounts needed for payroll service
function createHRAccountsForTerminationTest($company, $currency)
{
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

    // We need these mostly to prevent null errors if the service looks for them
    $company->update([
        'default_salary_payable_account_id' => $salaryPayableAccount->id,
        'default_salary_expense_account_id' => $salaryExpenseAccount->id,
    ]);
}
