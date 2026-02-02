<?php

namespace Tests\Feature\Scenarios;

use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Kezi\Accounting\Enums\Accounting\AccountType;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Journal;
use Kezi\Foundation\Models\Currency;
use Kezi\HR\Actions\HumanResources\CreateEmployeeAction;
use Kezi\HR\Actions\HumanResources\CreateEmploymentContractAction;
use Kezi\HR\Actions\HumanResources\CreatePaymentFromPayrollAction;
use Kezi\HR\Actions\HumanResources\ProcessPayrollAction;
use Kezi\HR\DataTransferObjects\HumanResources\CreateEmployeeDTO;
use Kezi\HR\DataTransferObjects\HumanResources\CreateEmploymentContractDTO;
use Kezi\HR\DataTransferObjects\HumanResources\ProcessPayrollDTO;
use Kezi\HR\Models\Employee;
use Kezi\HR\Models\EmploymentContract;
use Kezi\HR\Models\Payroll;
use Kezi\Payment\Models\Payment;

test('full HR cycle: employee -> contract -> payroll -> payment', function () {
    // --- Setup ---
    $company = Company::factory()->create();
    $currency = Currency::factory()->createSafely(['code' => 'USD', 'symbol' => '$']);

    // Create necessary accounts
    $bankAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Bank Account',
        'code' => '101000',
        'type' => AccountType::BankAndCash,
        'currency_id' => $currency->id,
    ]);

    $salaryPayableAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Salary Payable',
        'code' => '201000',
        'type' => AccountType::CurrentLiabilities,
        'currency_id' => $currency->id,
    ]);

    $salaryExpenseAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Salary Expense',
        'code' => '601000',
        'type' => AccountType::Expense,
        'currency_id' => $currency->id,
    ]);

    // Create Bank Journal
    $bankJournal = Journal::factory()->create([
        'company_id' => $company->id,
        'name' => 'Bank Journal',
        'type' => 'bank',
        'currency_id' => $currency->id,
        // Journal uses default_debit_account_id and default_credit_account_id
        'default_debit_account_id' => $bankAccount->id,
        'default_credit_account_id' => $bankAccount->id,
    ]);

    // Configure Company defaults
    $company->update([
        'default_bank_journal_id' => $bankJournal->id,
        'default_salary_payable_account_id' => $salaryPayableAccount->id,
        'default_salary_expense_account_id' => $salaryExpenseAccount->id,
    ]);

    $user = User::factory()->create();
    $hrManager = User::factory()->create();

    // --- Step 1: Create Employee ---
    $createEmployeeDTO = new CreateEmployeeDTO(
        company_id: $company->id,
        user_id: $user->id,
        department_id: null,
        position_id: null,
        manager_id: null,
        employee_number: '',
        first_name: 'John',
        last_name: 'Doe',
        email: 'john.doe@example.com',
        phone: null,
        date_of_birth: null,
        gender: 'male',
        marital_status: 'single',
        nationality: null,
        national_id: null,
        passport_number: null,
        address_line_1: null,
        address_line_2: null,
        city: null,
        state: null,
        zip_code: null,
        country: null,
        emergency_contact_name: null,
        emergency_contact_phone: null,
        emergency_contact_relationship: null,
        hire_date: Carbon::now(),
        termination_date: null,
        employment_status: 'active',
        employee_type: 'permanent',
        bank_name: null,
        bank_account_number: null,
        bank_routing_number: null,
        is_active: true,
        created_by_user_id: $hrManager->id
    );

    $employeeAction = app(CreateEmployeeAction::class);
    $employee = $employeeAction->execute($createEmployeeDTO);

    expect($employee)->toBeInstanceOf(Employee::class)
        ->and($employee->first_name)->toBe('John')
        ->and($employee->employee_number)->not->toBeNull();

    // --- Step 2: Create Contract ---
    $startDate = Carbon::now()->startOfMonth();
    $endDate = Carbon::now()->addYear()->endOfMonth();

    $createContractDTO = new CreateEmploymentContractDTO(
        company_id: $company->id,
        employee_id: $employee->id,
        currency_id: $currency->id,
        contract_number: '',
        contract_type: 'full_time',
        start_date: $startDate,
        end_date: $endDate,
        is_active: true,
        base_salary: 5000, // 5000 USD
        hourly_rate: null,
        pay_frequency: 'monthly',
        housing_allowance: 0,
        transport_allowance: 0,
        meal_allowance: 0,
        other_allowances: 0,
        working_hours_per_week: 40,
        working_days_per_week: 5,
        annual_leave_days: 21,
        sick_leave_days: 7,
        maternity_leave_days: 0,
        paternity_leave_days: 0,
        probation_period_months: 0,
        probation_end_date: null,
        notice_period_days: 30,
        terms_and_conditions: null,
        job_description: null,
        created_by_user_id: $hrManager->id
    );

    $contractAction = app(CreateEmploymentContractAction::class);
    $contract = $contractAction->execute($createContractDTO);

    expect($contract)->toBeInstanceOf(EmploymentContract::class)
        ->and($contract->base_salary->getAmount()->toFloat())->toBe(5000.0)
        ->and($contract->employee_id)->toBe($employee->id);

    // --- Step 3: Process Payroll ---
    $payPeriodStart = $startDate;
    $payPeriodEnd = $startDate->copy()->endOfMonth();
    $payDate = $payPeriodEnd;

    $processPayrollDTO = new ProcessPayrollDTO(
        company_id: $company->id,
        employee_id: $employee->id,
        currency_id: $currency->id,
        payroll_number: '',
        period_start_date: $payPeriodStart,
        period_end_date: $payPeriodEnd,
        pay_date: $payDate,
        pay_frequency: 'monthly',
        base_salary: 5000,
        overtime_amount: 0,
        housing_allowance: 0,
        transport_allowance: 0,
        meal_allowance: 0,
        other_allowances: 0,
        bonus: 0,
        commission: 0,
        income_tax: 0,
        social_security: 0,
        health_insurance: 0,
        pension_contribution: 0,
        loan_deduction: 0,
        other_deductions: 0,
        regular_hours: 160,
        overtime_hours: 0,
        payrollLines: [],
        notes: null,
        adjustments: [],
        processed_by_user_id: $hrManager->id
    );

    $payrollAction = app(ProcessPayrollAction::class);
    $payroll = $payrollAction->execute($processPayrollDTO);

    expect($payroll)->toBeInstanceOf(Payroll::class)
        ->and($payroll->net_salary->getAmount()->toFloat())->toBe(5000.0)
        ->and($payroll->status)->toBe('draft');

    // Transition to processed state (simulating approval/completion of processing)
    $payroll->update(['status' => 'processed']);

    // --- Step 4: Pay Payroll ---
    $payAction = app(CreatePaymentFromPayrollAction::class);
    $payment = $payAction->execute($payroll, $hrManager);

    // Assert Payment
    expect($payment)->toBeInstanceOf(Payment::class)
        ->and($payment->amount->getAmount()->toFloat())->toBe(5000.0)
        ->and($payment->company_id)->toBe($company->id);

    // Assert Payroll updated
    $payroll->refresh();
    expect($payroll->payment_id)->toBe($payment->id)
        ->and($payroll->status)->toBe('paid');

});
