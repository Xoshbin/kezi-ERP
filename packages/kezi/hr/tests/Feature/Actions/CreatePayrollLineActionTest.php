<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Models\Account;
use Kezi\Foundation\Models\Currency;
use Kezi\HR\Actions\HumanResources\CreatePayrollLineAction;
use Kezi\HR\DataTransferObjects\HumanResources\PayrollLineDTO;
use Kezi\HR\Models\Payroll;
use Kezi\HR\Models\PayrollLine;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

it('can create a payroll line with valid data (Happy Path)', function () {
    /** @var \Tests\TestCase $this */
    /** @var \App\Models\Company $company */
    /** @phpstan-ignore-next-line */
    $company = $this->company;

    /** @var \Kezi\Foundation\Models\Currency $currency */
    $currency = Currency::factory()->createSafely(['code' => 'USD']);

    /** @var \Kezi\HR\Models\Payroll $payroll */
    $payroll = Payroll::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
    ]);

    /** @var \Kezi\Accounting\Models\Account $account */
    $account = Account::factory()->create([
        'company_id' => $company->id,
    ]);

    $dto = new PayrollLineDTO(
        company_id: $company->id,
        account_id: $account->id,
        line_type: 'earning',
        code: 'BASIC_SALARY',
        description: ['en' => 'Basic Salary'],
        quantity: 1.0,
        unit: 'fixed',
        rate: Money::of(5000, 'USD'),
        amount: Money::of(5000, 'USD'),
        tax_rate: 0.0,
        is_taxable: true,
        is_statutory: false,
        debit_credit: 'debit',
        analytic_account_id: null,
        notes: null,
        reference: null
    );

    $action = app(CreatePayrollLineAction::class);
    $payrollLine = $action->execute($payroll, $dto);

    expect($payrollLine)
        ->toBeInstanceOf(PayrollLine::class);

    expect($payrollLine->payroll_id)->toBe($payroll->id);
    expect($payrollLine->company_id)->toBe($company->id);
    expect($payrollLine->account_id)->toBe($account->id);
    expect($payrollLine->line_type)->toBe('earning');
    expect($payrollLine->code)->toBe('BASIC_SALARY');
    expect($payrollLine->quantity)->toEqual(1.0);
    expect($payrollLine->unit)->toBe('fixed');
    expect($payrollLine->is_taxable)->toBeTrue();
    expect($payrollLine->is_statutory)->toBeFalse();
    expect($payrollLine->debit_credit)->toBe('debit');

    expect($payrollLine->description)->toBe(['en' => 'Basic Salary']);

    \Pest\Laravel\assertDatabaseHas('payroll_lines', [
        'id' => $payrollLine->id,
        'payroll_id' => $payroll->id,
        'code' => 'BASIC_SALARY',
        'line_type' => 'earning',
    ]);
});

it('handles money and currency conversion correctly when passed as strings', function () {
    /** @var \Tests\TestCase $this */
    /** @var \App\Models\Company $company */
    /** @phpstan-ignore-next-line */
    $company = $this->company;

    /** @var \Kezi\Foundation\Models\Currency $currency */
    $currency = Currency::factory()->createSafely(['code' => 'EUR']);

    /** @var \Kezi\HR\Models\Payroll $payroll */
    $payroll = Payroll::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
    ]);

    /** @var \Kezi\Accounting\Models\Account $account */
    $account = Account::factory()->create(['company_id' => $company->id]);

    $amountString = '123.45';
    $rateString = '10.50';

    $dto = new PayrollLineDTO(
        company_id: $company->id,
        account_id: $account->id,
        line_type: 'deduction',
        code: 'TAX',
        description: ['en' => 'Tax Deduction'],
        quantity: 1.0,
        unit: 'amount',
        rate: $rateString,
        amount: $amountString,
        tax_rate: 0.0,
        is_taxable: false,
        is_statutory: true,
        debit_credit: 'credit',
        analytic_account_id: null,
        notes: null,
        reference: null
    );

    $action = app(CreatePayrollLineAction::class);
    $payrollLine = $action->execute($payroll, $dto);

    $amount = $payrollLine->amount;
    expect($amount)->toBeInstanceOf(Money::class);
    /** @var Money $amount */
    expect($amount->getAmount()->toFloat())->toBe(123.45);
    expect($amount->getCurrency()->getCurrencyCode())->toBe('EUR');

    $rate = $payrollLine->rate;
    expect($rate)->toBeInstanceOf(Money::class);
    /** @var Money $rate */
    expect($rate->getAmount()->toFloat())->toBe(10.50);
});

it('handles different company currency correctly', function () {
    /** @var \Tests\TestCase $this */
    /** @var \App\Models\Company $company */
    /** @phpstan-ignore-next-line */
    $company = $this->company;

    $usd = Currency::factory()->createSafely(['code' => 'USD']);
    $company->currency_id = $usd->id;
    $company->save();

    $eur = Currency::factory()->createSafely(['code' => 'EUR']);

    /** @var \Kezi\HR\Models\Payroll $payroll */
    $payroll = Payroll::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $eur->id,
    ]);

    /** @var \Kezi\Accounting\Models\Account $account */
    $account = Account::factory()->create(['company_id' => $company->id]);

    $amountInfo = Money::of(100, 'EUR');

    $dto = new PayrollLineDTO(
        company_id: $company->id,
        account_id: $account->id,
        line_type: 'earning',
        code: 'BONUS',
        description: ['en' => 'Bonus'],
        quantity: 1.0,
        unit: 'fixed',
        rate: null,
        amount: $amountInfo,
        tax_rate: 0.0,
        is_taxable: true,
        is_statutory: false,
        debit_credit: 'debit',
        analytic_account_id: null,
        notes: null,
        reference: null
    );

    $action = app(CreatePayrollLineAction::class);
    $payrollLine = $action->execute($payroll, $dto);

    $amountCC = $payrollLine->amount_company_currency;
    expect($amountCC)->not->toBeNull();
    expect($amountCC)->toBeInstanceOf(Money::class);
    /** @var Money $amountCC */
    expect($amountCC->getAmount()->toFloat())->toBe(100.00);
});

it('correctly handles nullable fields', function () {
    /** @var \Tests\TestCase $this */
    /** @var \App\Models\Company $company */
    /** @phpstan-ignore-next-line */
    $company = $this->company;
    $currency = Currency::factory()->createSafely(['code' => 'USD']);

    /** @var \Kezi\HR\Models\Payroll $payroll */
    $payroll = Payroll::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
    ]);
    /** @var \Kezi\Accounting\Models\Account $account */
    $account = Account::factory()->create(['company_id' => $company->id]);

    $dto = new PayrollLineDTO(
        company_id: $company->id,
        account_id: $account->id,
        line_type: 'earning',
        code: 'BASIC',
        description: ['en' => 'Basic'],
        quantity: 1.0,
        unit: null,
        rate: null,
        amount: Money::of(1000, 'USD'),
        tax_rate: null,
        is_taxable: true,
        is_statutory: false,
        debit_credit: 'debit',
        analytic_account_id: null,
        notes: null,
        reference: null
    );

    $action = app(CreatePayrollLineAction::class);
    $payrollLine = $action->execute($payroll, $dto);

    expect($payrollLine->unit)->toBeNull();
    expect($payrollLine->rate)->toBeNull();
    expect($payrollLine->tax_rate)->toBeNull();
    expect($payrollLine->analytic_account_id)->toBeNull();
    expect($payrollLine->notes)->toBeNull();
    expect($payrollLine->reference)->toBeNull();
});

it('ensures relationship integrity', function () {
    /** @var \Tests\TestCase $this */
    /** @var \App\Models\Company $company */
    /** @phpstan-ignore-next-line */
    $company = $this->company;
    $currency = Currency::factory()->createSafely();

    /** @var \Kezi\HR\Models\Payroll $payroll */
    $payroll = Payroll::factory()->create(['company_id' => $company->id, 'currency_id' => $currency->id]);
    /** @var \Kezi\Accounting\Models\Account $account */
    $account = Account::factory()->create(['company_id' => $company->id]);

    $dto = new PayrollLineDTO(
        company_id: $company->id,
        account_id: $account->id,
        line_type: 'earning',
        code: 'TEST',
        description: ['en' => 'Test'],
        quantity: 1,
        unit: 'hrs',
        rate: Money::of(10, $currency->code),
        amount: Money::of(10, $currency->code),
        tax_rate: 0,
        is_taxable: false,
        is_statutory: false,
        debit_credit: 'debit',
        analytic_account_id: null,
        notes: null,
        reference: null
    );

    $action = app(CreatePayrollLineAction::class);
    $payrollLine = $action->execute($payroll, $dto);

    expect($payrollLine->payroll->is($payroll))->toBeTrue();
    expect($payrollLine->company->is($company))->toBeTrue();
    expect($payrollLine->account->is($account))->toBeTrue();
});
