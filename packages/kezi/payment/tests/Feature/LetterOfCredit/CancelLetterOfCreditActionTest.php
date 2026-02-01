<?php

use App\Models\User;
use Kezi\Payment\Actions\LetterOfCredit\CancelLetterOfCreditAction;
use Kezi\Payment\Enums\LetterOfCredit\LCStatus;
use Kezi\Payment\Models\LetterOfCredit;

it('cancels a draft LC successfully', function () {
    $user = User::factory()->create();
    $lc = LetterOfCredit::factory()->create([
        'status' => LCStatus::Draft,
    ]);

    $action = app(CancelLetterOfCreditAction::class);
    $action->execute($lc, $user);

    $lc->refresh();
    expect($lc->status)->toBe(LCStatus::Cancelled);
});

it('cancels an issued LC without utilization', function () {
    $user = User::factory()->create();
    $lc = LetterOfCredit::factory()->create([
        'status' => LCStatus::Issued,
        'utilized_amount' => \Brick\Money\Money::of(0, 'IQD'),
    ]);

    $action = app(CancelLetterOfCreditAction::class);
    $action->execute($lc, $user);

    $lc->refresh();
    expect($lc->status)->toBe(LCStatus::Cancelled);
});

it('throws exception when cancelling partially utilized LC', function () {
    $user = User::factory()->create();
    $lc = LetterOfCredit::factory()->create([
        'status' => LCStatus::PartiallyUtilized,
        'utilized_amount' => \Brick\Money\Money::of(10000, 'IQD'),
    ]);

    $action = app(CancelLetterOfCreditAction::class);
    $action->execute($lc, $user);
})->throws(RuntimeException::class, 'LC can only be cancelled if it is draft or issued without utilization');
