<?php

use App\Models\User;
use Brick\Money\Money;
use Modules\Payment\Actions\LetterOfCredit\UtilizeLetterOfCreditAction;
use Modules\Payment\DataTransferObjects\LetterOfCredit\UtilizeLCDTO;
use Modules\Payment\Enums\LetterOfCredit\LCStatus;
use Modules\Payment\Models\LCUtilization;
use Modules\Payment\Models\LetterOfCredit;
use Modules\Purchase\Models\VendorBill;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

it('utilizes LC against vendor bill successfully', function () {
    $user = User::factory()->create();
    $lc = LetterOfCredit::factory()->create([
        'status' => LCStatus::Issued,
        'amount' => Money::of(100000, 'IQD'),
        'balance' => Money::of(100000, 'IQD'),
        'utilized_amount' => Money::of(0, 'IQD'),
    ]);

    $vendorBill = VendorBill::factory()->create([
        'company_id' => $lc->company_id,
        'vendor_id' => $lc->vendor_id,
    ]);

    $dto = new UtilizeLCDTO(
        vendor_bill_id: $vendorBill->id,
        utilized_amount: Money::of(30000, 'IQD'),
        utilized_amount_company_currency: Money::of(30000, 'IQD'),
        utilization_date: now(),
    );

    $action = app(UtilizeLetterOfCreditAction::class);
    $utilization = $action->execute($lc, $dto, $user);

    expect($utilization)->toBeInstanceOf(LCUtilization::class)
        ->and($utilization->vendor_bill_id)->toBe($vendorBill->id)
        ->and($utilization->utilized_amount->isEqualTo(Money::of(30000, 'IQD')))->toBeTrue();

    $lc->refresh();
    expect($lc->utilized_amount->isEqualTo(Money::of(30000, 'IQD')))->toBeTrue()
        ->and($lc->balance->isEqualTo(Money::of(70000, 'IQD')))->toBeTrue()
        ->and($lc->status)->toBe(LCStatus::PartiallyUtilized);
});

it('marks LC as fully utilized when balance is zero', function () {
    $lc = LetterOfCredit::factory()->create([
        'company_id' => $this->company->id,
        'status' => LCStatus::PartiallyUtilized,
        'amount' => Money::of(100000, 'IQD'),
        'balance' => Money::of(50000, 'IQD'),
        'utilized_amount' => Money::of(50000, 'IQD'),
    ]);

    $baselineVendorBill = VendorBill::factory()->create([
        'company_id' => $lc->company_id,
        'vendor_id' => $lc->vendor_id,
    ]);

    // Create an initial utilization record so recalculateBalance() sees the 50000
    LCUtilization::create([
        'company_id' => $lc->company_id,
        'letter_of_credit_id' => $lc->id,
        'vendor_bill_id' => $baselineVendorBill->id,
        'utilized_amount' => Money::of(50000, 'IQD'),
        'utilized_amount_company_currency' => Money::of(50000, 'IQD'),
        'utilization_date' => now()->subDay(),
    ]);

    $vendorBill = VendorBill::factory()->create([
        'company_id' => $lc->company_id,
        'vendor_id' => $lc->vendor_id,
    ]);

    $dto = new UtilizeLCDTO(
        vendor_bill_id: $vendorBill->id,
        utilized_amount: Money::of(50000, 'IQD'),
        utilized_amount_company_currency: Money::of(50000, 'IQD'),
        utilization_date: now(),
    );

    $action = app(UtilizeLetterOfCreditAction::class);
    $action->execute($lc, $dto, $this->user);

    $lc->refresh();
    expect($lc->status)->toBe(LCStatus::FullyUtilized)
        ->and($lc->balance->isZero())->toBeTrue();
});

it('throws exception when utilization exceeds balance', function () {
    $user = User::factory()->create();
    $lc = LetterOfCredit::factory()->create([
        'status' => LCStatus::Issued,
        'amount' => Money::of(100000, 'IQD'),
        'balance' => Money::of(100000, 'IQD'),
    ]);

    $vendorBill = VendorBill::factory()->create([
        'company_id' => $lc->company_id,
    ]);

    $dto = new UtilizeLCDTO(
        vendor_bill_id: $vendorBill->id,
        utilized_amount: Money::of(150000, 'IQD'),
        utilized_amount_company_currency: Money::of(150000, 'IQD'),
        utilization_date: now(),
    );

    $action = app(UtilizeLetterOfCreditAction::class);
    $action->execute($lc, $dto, $user);
})->throws(RuntimeException::class, 'Utilization amount exceeds LC balance');
