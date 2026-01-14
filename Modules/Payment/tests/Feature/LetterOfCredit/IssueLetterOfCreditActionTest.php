<?php

use App\Models\User;
use Modules\Payment\Actions\LetterOfCredit\IssueLetterOfCreditAction;
use Modules\Payment\DataTransferObjects\LetterOfCredit\IssueLetterOfCreditDTO;
use Modules\Payment\Enums\LetterOfCredit\LCStatus;
use Modules\Payment\Models\LetterOfCredit;

it('issues a draft LC successfully', function () {
    $user = User::factory()->create();
    $lc = LetterOfCredit::factory()->create([
        'status' => LCStatus::Draft,
    ]);

    $dto = new IssueLetterOfCreditDTO(
        bank_reference: 'BANK-REF-12345',
        issue_date: now(),
    );

    $action = app(IssueLetterOfCreditAction::class);
    $action->execute($lc, $dto, $user);

    $lc->refresh();

    expect($lc->status)->toBe(LCStatus::Issued)
        ->and($lc->bank_reference)->toBe('BANK-REF-12345');
});

it('throws exception when trying to issue non-draft LC', function () {
    $user = User::factory()->create();
    $lc = LetterOfCredit::factory()->create([
        'status' => LCStatus::Issued,
    ]);

    $dto = new IssueLetterOfCreditDTO(
        bank_reference: 'BANK-REF-99999',
        issue_date: now(),
    );

    $action = app(IssueLetterOfCreditAction::class);
    $action->execute($lc, $dto, $user);
})->throws(RuntimeException::class, 'Only draft LCs can be issued');
