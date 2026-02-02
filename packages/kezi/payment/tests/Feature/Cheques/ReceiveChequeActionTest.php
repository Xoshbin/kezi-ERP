<?php

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Models\Journal;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Payment\Actions\Cheques\ReceiveChequeAction;
use Kezi\Payment\DataTransferObjects\Cheques\CreateChequeDTO;
use Kezi\Payment\Enums\Cheques\ChequeStatus;
use Kezi\Payment\Enums\Cheques\ChequeType;
use Kezi\Payment\Models\Cheque;

uses(RefreshDatabase::class);

it('can receive a customer cheque', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    // $user->companies()->attach($company); // Skip attach to avoid column error

    // Use existing IQD currency or create it if not exists to avoid unique constraint error
    $currency = Currency::firstWhere('code', 'IQD') ?? Currency::factory()->createSafely(['code' => 'IQD']);
    // Bank Journal
    $journal = Journal::factory()->create(['company_id' => $company->id]);
    $partner = Partner::factory()->create(['company_id' => $company->id]);

    $dto = new CreateChequeDTO(
        company_id: $company->id,
        journal_id: $journal->id,
        partner_id: $partner->id,
        currency_id: $currency->id,
        cheque_number: 'CHQ-REC-001',
        amount: Money::of(75000, 'IQD'),
        issue_date: now()->format('Y-m-d'),
        due_date: now()->addDays(15)->format('Y-m-d'),
        type: ChequeType::Receivable,
        payee_name: 'Our Company',
        bank_name: 'Customer Bank',
        memo: 'Payment for Invoice #123'
    );

    $action = app(ReceiveChequeAction::class);
    $cheque = $action->execute($dto, $user);

    expect($cheque)
        ->toBeInstanceOf(Cheque::class)
        ->status->toBe(ChequeStatus::Draft)
        ->type->toBe(ChequeType::Receivable)
        ->cheque_number->toBe('CHQ-REC-001')
        ->cheque_number->toBe('CHQ-REC-001')
        ->bank_name->toBe('Customer Bank');

    expect($cheque->amount->isEqualTo(Money::of(75000, 'IQD')))->toBeTrue();
});
