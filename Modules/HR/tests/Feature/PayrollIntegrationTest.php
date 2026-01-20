<?php

namespace Modules\HR\Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Enums\Accounting\JournalType;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Modules\Foundation\Enums\Partners\PartnerType;
use Modules\Foundation\Models\Currency;
use Modules\HR\Actions\HumanResources\CreatePaymentFromPayrollAction;
use Modules\HR\Actions\HumanResources\ProcessPayrollAction;
use Modules\HR\DataTransferObjects\HumanResources\ProcessPayrollDTO;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Payroll;
use Modules\Payment\Enums\Payments\PaymentStatus;
use Modules\Payment\Models\Payment;

uses(RefreshDatabase::class);

/**
 * Helper to create shared test context.
 * Returns an array of created models.
 *
 * @return array<string, mixed>
 */
function createPayrollTestContext(): array
{
    $user = User::factory()->create();
    $currency = Currency::firstOrCreate(
        ['code' => 'IQD'],
        [
            'name' => 'Iraqi Dinar',
            'symbol' => 'IQD',
            'decimal_places' => 3,
            'is_active' => true,
        ]
    );

    $company = Company::factory()->create([
        'currency_id' => $currency->id,
    ]);

    // Create necessary accounts
    $bankAccount = Account::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'code' => '1000',
        'name' => 'Bank',
        'type' => 'bank_and_cash',
    ]);

    $salaryPayableAccount = Account::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'code' => '2000',
        'name' => 'Salary Payable',
        'type' => 'current_liabilities',
    ]);

    // Create Journal
    $bankJournal = Journal::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'type' => JournalType::Bank,
        'name' => 'Bank Journal',
        'default_debit_account_id' => $bankAccount->id,
        'default_credit_account_id' => $bankAccount->id,
    ]);

    $company->update([
        'default_bank_journal_id' => $bankJournal->id,
        'default_salary_payable_account_id' => $salaryPayableAccount->id,
    ]);

    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'is_active' => true,
        'employment_status' => 'active',
        'email' => 'employee@example.com',
    ]);

    return compact('user', 'currency', 'company', 'employee', 'bankJournal', 'salaryPayableAccount', 'bankAccount');
}

test('full payroll workflow: process payroll then pay it', function () {
    $context = createPayrollTestContext();
    /** @var User $user */
    $user = $context['user'];
    /** @var Currency $currency */
    $currency = $context['currency'];
    /** @var Company $company */
    $company = $context['company'];
    /** @var Employee $employee */
    $employee = $context['employee'];

    // 1. Process Payroll (Create Draft)
    $dto = new ProcessPayrollDTO(
        company_id: $company->id,
        employee_id: $employee->id,
        currency_id: $currency->id,
        payroll_number: '',
        period_start_date: '2025-08-01',
        period_end_date: '2025-08-31',
        pay_date: '2025-08-31',
        pay_frequency: 'monthly',
        base_salary: Money::of(1000000, 'IQD'),
        overtime_amount: Money::of(100000, 'IQD'),
        housing_allowance: Money::of(0, 'IQD'),
        transport_allowance: Money::of(0, 'IQD'),
        meal_allowance: Money::of(0, 'IQD'),
        other_allowances: Money::of(0, 'IQD'),
        bonus: Money::of(0, 'IQD'),
        commission: Money::of(0, 'IQD'),
        income_tax: Money::of(50000, 'IQD'),
        social_security: Money::of(20000, 'IQD'),
        health_insurance: Money::of(0, 'IQD'),
        pension_contribution: Money::of(0, 'IQD'),
        loan_deduction: Money::of(0, 'IQD'),
        other_deductions: Money::of(0, 'IQD'),
        regular_hours: 160,
        overtime_hours: 5,
        processed_by_user_id: $user->id,
        notes: null,
        adjustments: [],
        payrollLines: []
    );

    $processAction = app(ProcessPayrollAction::class);
    $payroll = $processAction->execute($dto);

    // Verify Processed correct totals
    expect($payroll)->toBeInstanceOf(Payroll::class);
    // Gross = 1000000 + 100000 = 1100000
    expect($payroll->gross_salary->isEqualTo(Money::of(1100000, 'IQD')))->toBeTrue();
    // Deductions = 50000 + 20000 = 70000
    expect($payroll->total_deductions->isEqualTo(Money::of(70000, 'IQD')))->toBeTrue();
    // Net = 1100000 - 70000 = 1030000
    expect($payroll->net_salary->isEqualTo(Money::of(1030000, 'IQD')))->toBeTrue();

    // Verify status
    expect($payroll->status)->toBe('draft');

    // Simulate Approval/Processing transition
    $payroll->update(['status' => 'processed']);

    // 2. Create Payment
    $createPaymentAction = app(CreatePaymentFromPayrollAction::class);
    $payment = $createPaymentAction->execute($payroll, $user);

    expect($payment)->toBeInstanceOf(Payment::class);
    expect($payment->amount->isEqualTo(Money::of(1030000, 'IQD')))->toBeTrue();
    expect($payment->status)->toBe(PaymentStatus::Draft);

    $payroll->refresh();
    expect($payroll->status)->toBe('paid');
    expect($payroll->payment_id)->toBe($payment->id);

    // Check Partner creation
    $partner = \Modules\Foundation\Models\Partner::where('email', $employee->email)->first();
    expect($partner)->not->toBeNull();
    expect($partner->type)->toBe(PartnerType::Vendor);
});

test('payroll observer recalculates totals on update', function () {
    $context = createPayrollTestContext();
    /** @var Currency $currency */
    $currency = $context['currency'];
    /** @var Company $company */
    $company = $context['company'];
    /** @var Employee $employee */
    $employee = $context['employee'];

    $payroll = Payroll::create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'currency_id' => $currency->id,
        'period_start_date' => '2025-08-01',
        'period_end_date' => '2025-08-31',
        'pay_date' => '2025-08-31',
        'pay_frequency' => 'monthly',
        'base_salary' => Money::of(1000000, 'IQD'),
        'overtime_amount' => Money::of(0, 'IQD'),
        'housing_allowance' => Money::of(0, 'IQD'),
        'transport_allowance' => Money::of(0, 'IQD'),
        'meal_allowance' => Money::of(0, 'IQD'),
        'other_allowances' => Money::of(0, 'IQD'),
        'bonus' => Money::of(0, 'IQD'),
        'commission' => Money::of(0, 'IQD'),
        'income_tax' => Money::of(0, 'IQD'),
        'social_security' => Money::of(0, 'IQD'),
        'health_insurance' => Money::of(0, 'IQD'),
        'pension_contribution' => Money::of(0, 'IQD'),
        'other_deductions' => Money::of(0, 'IQD'),
    ]);

    // Initial check
    expect($payroll->gross_salary->isEqualTo(Money::of(1000000, 'IQD')))->toBeTrue();

    // UPDATE
    $payroll->update([
        'base_salary' => Money::of(2000000, 'IQD'),
        'bonus' => Money::of(500000, 'IQD'),
    ]);

    // Check if totals updated automatically
    // 2,000,000 + 500,000 = 2,500,000
    expect($payroll->gross_salary->isEqualTo(Money::of(2500000, 'IQD')))->toBeTrue();
});
