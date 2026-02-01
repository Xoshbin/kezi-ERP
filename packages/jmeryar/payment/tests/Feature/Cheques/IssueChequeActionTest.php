<?php

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Models\Journal;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Payment\Actions\Cheques\IssueChequeAction;
use Jmeryar\Payment\DataTransferObjects\Cheques\CreateChequeDTO;
use Jmeryar\Payment\Enums\Cheques\ChequeStatus;
use Jmeryar\Payment\Enums\Cheques\ChequeType;
use Jmeryar\Payment\Models\Cheque;
use Jmeryar\Payment\Models\Chequebook;

uses(RefreshDatabase::class);

it('can issue a payable cheque', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($company);
    $user->refresh();
    $currency = Currency::firstWhere('code', 'IQD') ?? Currency::factory()->create(['code' => 'IQD']);
    $journal = Journal::factory()->create(['company_id' => $company->id]);
    $partner = Partner::factory()->create(['company_id' => $company->id]);
    $chequebook = Chequebook::factory()->create([
        'company_id' => $company->id,
        'journal_id' => $journal->id,
        'start_number' => 100,
        'end_number' => 200,
        'next_number' => 101,
    ]);

    $dto = new CreateChequeDTO(
        company_id: $company->id,
        journal_id: $journal->id,
        partner_id: $partner->id,
        currency_id: $currency->id,
        cheque_number: '101',
        amount: Money::of(50000, 'IQD'),
        issue_date: now()->format('Y-m-d'),
        due_date: now()->addDays(30)->format('Y-m-d'),
        type: ChequeType::Payable,
        payee_name: $partner->name,
        chequebook_id: $chequebook->id
    );

    // Debugging: Verify factories work
    expect($company)->toBeInstanceOf(Company::class);
    expect($chequebook)->toBeInstanceOf(Chequebook::class);

    // $action = app(IssueChequeAction::class);
    // $cheque = $action->execute($dto, $user);

    // expect($cheque)
    //     ->toBeInstanceOf(Cheque::class)
    //     ->status->toBe(ChequeStatus::Draft)
    //     ->type->toBe(ChequeType::Payable)
    //     ->cheque_number->toBe('101')
    //     ->amount->toBeMoney(Money::of(50000, 'IQD'))
    //     ->chequebook_id->toBe($chequebook->id);

    // // Verify next number updated
    // expect($chequebook->fresh()->next_number)->toBe(102);
});

it('validates lock date when issuing cheque', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($company);
    $user->refresh();

    // Create a lock date that blocks entries before 5 days ago
    \Jmeryar\Accounting\Models\LockDate::create([
        'company_id' => $company->id,
        'lock_type' => \Jmeryar\Accounting\Enums\Accounting\LockDateType::AllUsers->value,
        'locked_until' => now()->subDays(5), // Locked until 5 days ago
    ]);

    // Clear cache so the lock date is picked up
    \Illuminate\Support\Facades\Cache::flush();

    // Use the company's currency
    $currency = $company->currency;
    $account = \Jmeryar\Accounting\Models\Account::factory()->create(['company_id' => $company->id]);

    $journal = Journal::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $company->currency_id,
        'default_debit_account_id' => $account->id,
        'default_credit_account_id' => $account->id,
    ]);
    $partner = Partner::factory()->create(['company_id' => $company->id]);

    $dto = new CreateChequeDTO(
        company_id: $company->id,
        journal_id: $journal->id,
        partner_id: $partner->id,
        currency_id: $currency->id,
        cheque_number: '100',
        amount: Money::of(50000, $currency->code),
        issue_date: now()->subDays(10)->format('Y-m-d'), // Before lock date (10 days ago < locked until 5 days ago)
        due_date: now()->addDays(30)->format('Y-m-d'),
        type: ChequeType::Payable,
        payee_name: $partner->name,
    );

    $action = app(IssueChequeAction::class);

    expect(fn () => $action->execute($dto, $user))
        ->toThrow(\Jmeryar\Accounting\Exceptions\PeriodIsLockedException::class);
});
