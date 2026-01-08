<?php

use Brick\Money\Money;
use Modules\Payment\Enums\LetterOfCredit\LCStatus;
use Modules\Payment\Models\LetterOfCredit;

it('calculates balance correctly', function () {
    $lc = LetterOfCredit::factory()->create([
        'amount' => Money::of(100000, 'IQD'),
        'utilized_amount' => Money::of(30000, 'IQD'),
        'balance' => Money::of(70000, 'IQD'),
    ]);

    expect($lc->balance->isEqualTo(Money::of(70000, 'IQD')))->toBeTrue();
});

it('checks if LC is expired', function () {
    $expiredLC = LetterOfCredit::factory()->create([
        'expiry_date' => now()->subDays(10),
        'status' => LCStatus::Issued,
    ]);

    $activeLC = LetterOfCredit::factory()->create([
        'expiry_date' => now()->addDays(10),
        'status' => LCStatus::Issued,
    ]);

    expect($expiredLC->isExpired())->toBeTrue()
        ->and($activeLC->isExpired())->toBeFalse();
});

it('checks if LC can be utilized', function () {
    $draftLC = LetterOfCredit::factory()->create([
        'status' => LCStatus::Draft,
        'expiry_date' => now()->addDays(10),
    ]);

    $issuedLC = LetterOfCredit::factory()->create([
        'status' => LCStatus::Issued,
        'expiry_date' => now()->addDays(10),
    ]);

    $expiredLC = LetterOfCredit::factory()->create([
        'status' => LCStatus::Issued,
        'expiry_date' => now()->subDays(10),
    ]);

    expect($draftLC->canBeUtilized())->toBeFalse()
        ->and($issuedLC->canBeUtilized())->toBeTrue()
        ->and($expiredLC->canBeUtilized())->toBeFalse();
});
