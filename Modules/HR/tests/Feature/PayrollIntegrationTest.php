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
use Modules\HR\Actions\HumanResources\CreatePayrollLineAction;
use Modules\HR\Actions\HumanResources\ProcessPayrollAction;
use Modules\HR\DataTransferObjects\HumanResources\ProcessPayrollDTO;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Payroll;
use Modules\Payment\Actions\Payments\CreatePaymentAction;
use Modules\Payment\Enums\Payments\PaymentStatus;
use Modules\Payment\Models\Payment;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->currency = Currency::firstOrCreate(
        ['code' => 'IQD'],
        [
            'name' => 'Iraqi Dinar',
            'symbol' => 'IQD',
            'decimal_places' => 3,
            'is_active' => true,
        ]
    );

    $this->company = Company::factory()->create([
        'currency_id' => $this->currency->id,
    ]);

    // Create necessary accounts
    $this->bankAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->currency->id,
        'code' => '1000',
        'name' => 'Bank',
        'type' => 'bank_and_cash',
    ]);

    $this->salaryPayableAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->currency->id,
        'code' => '2000',
        'name' => 'Salary Payable',
        'type' => 'current_liabilities',
    ]);

    // Create Journal
    $this->bankJournal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->currency->id,
        'type' => JournalType::Bank,
        'name' => 'Bank Journal',
        'default_debit_account_id' => $this->bankAccount->id,
        'default_credit_account_id' => $this->bankAccount->id,
    ]);

    $this->company->update([
        'default_bank_journal_id' => $this->bankJournal->id,
        'default_salary_payable_account_id' => $this->salaryPayableAccount->id,
    ]);

    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'is_active' => true,
        'employment_status' => 'active',
        'email' => 'employee@example.com',
    ]);
});

test('full payroll workflow: process payroll then pay it', function () {
    // 1. Process Payroll (Create Draft)
    $dto = new ProcessPayrollDTO(
        company_id: $this->company->id,
        employee_id: $this->employee->id,
        currency_id: $this->currency->id,
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
        processed_by_user_id: $this->user->id,
        notes: null,
        adjustments: [],
        payrollLines: []
    );

    // Use binding or instantiate manually. Action has dependency on CreatePayrollLineAction.
    // We should rely on container.
    $processAction = app(ProcessPayrollAction::class);
    $payroll = $processAction->execute($dto);

    // Verify Processed correct totals
    expect($payroll)->toBeInstanceOf(Payroll::class);
    // Gross = 1000000 + 100000 = 1100000

    // Wait, IQD has 3 decimals? Existing tests use 3 decimals in Money::of?
    // In existing test: Money::of(5000000, 'IQD') -> 5,000,000 IQD.
    // Brick\Money\Money handles context.
    // Let's rely on checking `isEqualTo`.
    expect($payroll->gross_salary->isEqualTo(Money::of(1100000, 'IQD')))->toBeTrue();
    // Deductions = 50000 + 20000 = 70000
    expect($payroll->total_deductions->isEqualTo(Money::of(70000, 'IQD')))->toBeTrue();
    // Net = 1100000 - 70000 = 1030000
    expect($payroll->net_salary->isEqualTo(Money::of(1030000, 'IQD')))->toBeTrue();

    // Verify status
    // As observed, ProcessPayrollAction likely leaves it as 'draft' or doesn't set 'processed'.
    expect($payroll->status)->toBe('draft');

    // Simulate Approval/Processing transition
    $payroll->update(['status' => 'processed']);

    // 2. Create Payment
    $createPaymentAction = app(CreatePaymentFromPayrollAction::class);
    $payment = $createPaymentAction->execute($payroll, $this->user);

    expect($payment)->toBeInstanceOf(Payment::class);
    expect($payment->amount->isEqualTo(Money::of(1030000, 'IQD')))->toBeTrue();
    expect($payment->status)->toBe(PaymentStatus::Draft);
    // CreatePaymentAction usually creates draft or posted depending on args.
    // CreatePaymentDTO doesn't have status. CreatePaymentAction default status?
    // Let's assume it creates and maybe we check relation.

    $payroll->refresh();
    expect($payroll->status)->toBe('paid');
    expect($payroll->payment_id)->toBe($payment->id);

    // Check Partner creation
    $partner = \Modules\Foundation\Models\Partner::where('email', $this->employee->email)->first();
    expect($partner)->not->toBeNull();
    expect($partner->type)->toBe(PartnerType::Vendor);
});

test('payroll observer recalculates totals on update', function () {
    $payroll = Payroll::create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'currency_id' => $this->currency->id,
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
        // Totals calculated by observer on create
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
