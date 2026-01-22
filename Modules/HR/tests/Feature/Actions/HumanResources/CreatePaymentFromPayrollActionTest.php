<?php

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Enums\Accounting\JournalType;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Modules\Foundation\Models\Partner;
use Modules\HR\Actions\HumanResources\CreatePaymentFromPayrollAction;
use Modules\HR\Models\Employee;
use Modules\HR\Models\EmploymentContract;
use Modules\HR\Models\Payroll;
use Modules\Payment\Enums\Payments\PaymentMethod;
use Modules\Payment\Enums\Payments\PaymentStatus;
use Modules\Payment\Enums\Payments\PaymentType;
use Modules\Payment\Models\Payment;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->payingUser = User::factory()->create();
    $this->action = app(CreatePaymentFromPayrollAction::class);

    // Create HR accounts
    $this->salaryExpenseAccount = Account::factory()->for($this->company)->create([
        'name' => 'Salary Expense',
        'code' => '6100',
        'type' => 'expense',
    ]);

    $this->salaryPayableAccount = Account::factory()->for($this->company)->create([
        'name' => 'Salary Payable',
        'code' => '2100',
        'type' => 'current_liabilities',
    ]);

    $this->bankAccount = Account::factory()->for($this->company)->create([
        'name' => 'Bank Account',
        'code' => '1100',
        'type' => 'current_assets',
    ]);

    $this->bankJournal = Journal::factory()->for($this->company)->create([
        'name' => 'Bank Journal',
        'type' => JournalType::Bank,
        'default_debit_account_id' => $this->bankAccount->id,
        'default_credit_account_id' => $this->bankAccount->id,
    ]);

    // Set company defaults required for payroll payments
    $this->company->update([
        'default_salary_expense_account_id' => $this->salaryExpenseAccount->id,
        'default_salary_payable_account_id' => $this->salaryPayableAccount->id,
        'default_bank_journal_id' => $this->bankJournal->id,
    ]);

    // Create an employee with the same email
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'email' => 'john.doe@test.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone' => '+1234567890',
    ]);

    // Create an employment contract for the employee
    $this->contract = EmploymentContract::factory()->create([
        'employee_id' => $this->employee->id,
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
        'base_salary' => Money::of(5000, $this->company->currency->code),
        'is_active' => true,
    ]);
});

describe('CreatePaymentFromPayrollAction', function () {
    describe('successful payment creation', function () {
        it('can create payment from processed payroll', function () {
            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'currency_id' => $this->company->currency_id,
                'status' => 'processed',
                'base_salary' => 4500,
                'pay_date' => now(),
            ]);

            $payment = $this->action->execute($payroll, $this->payingUser);

            expect($payment)
                ->toBeInstanceOf(Payment::class)
                ->company_id->toBe($this->company->id)
                ->currency_id->toBe($payroll->currency_id)
                ->payment_type->toBe(PaymentType::Outbound)
                ->payment_method->toBe(PaymentMethod::BankTransfer)
                ->status->toBe(PaymentStatus::Draft);

            // Verify amount matches net salary
            expect((string) $payment->amount->getAmount())->toBe('4500.000');
        });

        it('links payment to payroll record', function () {
            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'currency_id' => $this->company->currency_id,
                'status' => 'processed',
                'base_salary' => 3000,
                'pay_date' => now(),
            ]);

            $payment = $this->action->execute($payroll, $this->payingUser);

            $payroll->refresh();
            expect($payroll)
                ->payment_id->toBe($payment->id)
                ->status->toBe('paid');
        });

        it('creates payment with correct payment date from payroll pay_date', function () {
            $payDate = now()->subDays(5);
            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'currency_id' => $this->company->currency_id,
                'status' => 'processed',
                'base_salary' => 2000,
                'pay_date' => $payDate,
            ]);

            $payment = $this->action->execute($payroll, $this->payingUser);

            expect($payment->payment_date->format('Y-m-d'))->toBe($payDate->format('Y-m-d'));
        });

        it('creates payment with reference containing employee name and payroll number', function () {
            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'currency_id' => $this->company->currency_id,
                'status' => 'processed',
                'base_salary' => 2500,
                'payroll_number' => 'PAY-12345',
                'pay_date' => now(),
            ]);

            $payment = $this->action->execute($payroll, $this->payingUser);

            expect($payment->reference)
                ->toContain('John')
                ->toContain('Doe')
                ->toContain('PAY-12345');
        });

        it('uses correct bank journal for payment', function () {
            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'currency_id' => $this->company->currency_id,
                'status' => 'processed',
                'base_salary' => 1500,
                'pay_date' => now(),
            ]);

            $payment = $this->action->execute($payroll, $this->payingUser);

            expect($payment->journal_id)->toBe($this->bankJournal->id);
        });

        it('increases total payment count after payment creation', function () {
            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'currency_id' => $this->company->currency_id,
                'status' => 'processed',
                'base_salary' => 1000,
                'pay_date' => now(),
            ]);

            $initialPaymentCount = Payment::count();

            $this->action->execute($payroll, $this->payingUser);

            expect(Payment::count())->toBe($initialPaymentCount + 1);
        });
    });

    describe('partner creation for employee', function () {
        it('creates partner for employee if not exists', function () {
            $initialPartnerCount = Partner::count();

            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'currency_id' => $this->company->currency_id,
                'status' => 'processed',
                'base_salary' => 2000,
                'pay_date' => now(),
            ]);

            $payment = $this->action->execute($payroll, $this->payingUser);

            expect(Partner::count())->toBe($initialPartnerCount + 1);

            $partner = Partner::where('email', $this->employee->email)->first();
            expect($partner)
                ->not->toBeNull()
                ->name->toBe('John Doe')
                ->email->toBe('john.doe@test.com')
                ->type->toBe(\Modules\Foundation\Enums\Partners\PartnerType::Vendor);
        });

        it('reuses existing partner for employee with matching email', function () {
            // Create existing partner with same email
            $existingPartner = Partner::create([
                'company_id' => $this->company->id,
                'name' => 'John Doe',
                'email' => 'john.doe@test.com',
                'type' => \Modules\Foundation\Enums\Partners\PartnerType::Vendor,
            ]);

            $initialPartnerCount = Partner::count();

            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'currency_id' => $this->company->currency_id,
                'status' => 'processed',
                'base_salary' => 2000,
                'pay_date' => now(),
            ]);

            $payment = $this->action->execute($payroll, $this->payingUser);

            // No new partner should be created
            expect(Partner::count())->toBe($initialPartnerCount);

            // Payment should be linked to existing partner
            expect($payment->paid_to_from_partner_id)->toBe($existingPartner->id);
        });

        it('links payment to partner for employee', function () {
            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'currency_id' => $this->company->currency_id,
                'status' => 'processed',
                'base_salary' => 1500,
                'pay_date' => now(),
            ]);

            $payment = $this->action->execute($payroll, $this->payingUser);

            expect($payment->paid_to_from_partner_id)->not->toBeNull();

            $partner = Partner::find($payment->paid_to_from_partner_id);
            expect($partner->email)->toBe($this->employee->email);
        });
    });

    describe('status validation', function () {
        it('cannot create payment for draft payroll', function () {
            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'currency_id' => $this->company->currency_id,
                'status' => 'draft',
                'base_salary' => 1000,
                'pay_date' => now(),
            ]);

            expect(fn () => $this->action->execute($payroll, $this->payingUser))
                ->toThrow(\InvalidArgumentException::class, 'Only processed payrolls can be paid.');
        });

        it('cannot create payment for pending payroll', function () {
            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'currency_id' => $this->company->currency_id,
                'status' => 'pending',
                'base_salary' => 1000,
                'pay_date' => now(),
            ]);

            expect(fn () => $this->action->execute($payroll, $this->payingUser))
                ->toThrow(\InvalidArgumentException::class, 'Only processed payrolls can be paid.');
        });

        it('cannot create payment for already paid payroll', function () {
            // Create a processed payroll and pay it
            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'currency_id' => $this->company->currency_id,
                'status' => 'processed',
                'base_salary' => 2000,
                'pay_date' => now(),
            ]);

            // Pay first time
            $this->action->execute($payroll, $this->payingUser);

            // Attempt to pay again - should fail because payment_id is now set
            // Need to reload with processed status to simulate edge case
            $payroll->refresh();
            $payroll->status = 'processed'; // Simulate someone trying to change status back
            $payroll->save();

            expect(fn () => $this->action->execute($payroll, $this->payingUser))
                ->toThrow(\InvalidArgumentException::class, 'Payroll has already been paid.');
        });

        it('cannot create payment for cancelled payroll', function () {
            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'currency_id' => $this->company->currency_id,
                'status' => 'cancelled',
                'base_salary' => 1000,
                'pay_date' => now(),
            ]);

            expect(fn () => $this->action->execute($payroll, $this->payingUser))
                ->toThrow(\InvalidArgumentException::class, 'Only processed payrolls can be paid.');
        });
    });

    describe('configuration validation', function () {
        it('throws exception when no default bank journal configured', function () {
            $this->company->update([
                'default_bank_journal_id' => null,
            ]);

            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'currency_id' => $this->company->currency_id,
                'status' => 'processed',
                'base_salary' => 1000,
                'pay_date' => now(),
            ]);

            expect(fn () => $this->action->execute($payroll, $this->payingUser))
                ->toThrow(\InvalidArgumentException::class, 'No default bank journal found for company.');
        });

        it('throws exception when no salary payable account configured', function () {
            $this->company->update([
                'default_salary_payable_account_id' => null,
            ]);

            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'currency_id' => $this->company->currency_id,
                'status' => 'processed',
                'base_salary' => 1000,
                'pay_date' => now(),
            ]);

            expect(fn () => $this->action->execute($payroll, $this->payingUser))
                ->toThrow(\InvalidArgumentException::class, 'No default salary payable account configured for company.');
        });
    });

    describe('currency handling', function () {
        it('creates payment with same currency as payroll', function () {
            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'currency_id' => $this->company->currency_id,
                'status' => 'processed',
                'base_salary' => 3500,
                'pay_date' => now(),
            ]);

            $payment = $this->action->execute($payroll, $this->payingUser);

            expect($payment->currency_id)->toBe($payroll->currency_id);
        });

        it('handles payroll with different amounts correctly', function () {
            // Test with various amounts to ensure Money handling is correct
            $amounts = [100, 1000.50, 99999.99, 0.01];

            foreach ($amounts as $amount) {
                $payroll = Payroll::factory()->create([
                    'company_id' => $this->company->id,
                    'employee_id' => $this->employee->id,
                    'currency_id' => $this->company->currency_id,
                    'status' => 'processed',
                    'base_salary' => $amount,
                    'pay_date' => now(),
                    'payment_id' => null,
                ]);

                $payment = $this->action->execute($payroll, $this->payingUser);

                expect((string) $payment->amount->getAmount())->toBe(number_format($amount, 3, '.', ''));
            }
        });
    });

    describe('payroll helper methods interaction', function () {
        it('payroll canBePaid returns true for processed payroll without payment', function () {
            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'currency_id' => $this->company->currency_id,
                'status' => 'processed',
                'base_salary' => 2000,
                'pay_date' => now(),
                'payment_id' => null,
            ]);

            expect($payroll->canBePaid())->toBeTrue();
            expect($payroll->isPaid())->toBeFalse();
        });

        it('payroll canBePaid returns false after payment created', function () {
            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'currency_id' => $this->company->currency_id,
                'status' => 'processed',
                'base_salary' => 2000,
                'pay_date' => now(),
            ]);

            $this->action->execute($payroll, $this->payingUser);
            $payroll->refresh();

            expect($payroll->canBePaid())->toBeFalse();
            expect($payroll->isPaid())->toBeTrue();
        });

        it('payroll canBePaid returns false for draft status', function () {
            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'currency_id' => $this->company->currency_id,
                'status' => 'draft',
                'base_salary' => 2000,
                'pay_date' => now(),
                'payment_id' => null,
            ]);

            expect($payroll->canBePaid())->toBeFalse();
        });
    });

    describe('edge cases', function () {
        it('handles zero net salary payroll', function () {
            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'currency_id' => $this->company->currency_id,
                'status' => 'processed',
                'base_salary' => 0,
                'pay_date' => now(),
            ]);

            $payment = $this->action->execute($payroll, $this->payingUser);

            expect($payment->amount->isZero())->toBeTrue();
            expect($payroll->refresh()->status)->toBe('paid');
        });

        it('handles large salary amounts', function () {
            $largeAmount = 9999999.99;

            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'currency_id' => $this->company->currency_id,
                'status' => 'processed',
                'base_salary' => $largeAmount,
                'pay_date' => now(),
            ]);

            $payment = $this->action->execute($payroll, $this->payingUser);

            expect((string) $payment->amount->getAmount())->toBe('9999999.990');
        });
    });
});
