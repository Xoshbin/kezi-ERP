<?php

use Jmeryar\Payment\Enums\Cheques\ChequeStatus;

it('can get labels for all statuses', function () {
    foreach (ChequeStatus::cases() as $status) {
        expect($status->getLabel())->toBeString();
    }
});

it('has correct color for each status', function () {
    expect(ChequeStatus::Draft->getColor())->toBe('gray')
        ->and(ChequeStatus::Printed->getColor())->toBe('info')
        ->and(ChequeStatus::HandedOver->getColor())->toBe('warning')
        ->and(ChequeStatus::Deposited->getColor())->toBe('warning')
        ->and(ChequeStatus::Cleared->getColor())->toBe('success')
        ->and(ChequeStatus::Bounced->getColor())->toBe('danger')
        ->and(ChequeStatus::Cancelled->getColor())->toBe('gray')
        ->and(ChequeStatus::Voided->getColor())->toBe('gray');
});
