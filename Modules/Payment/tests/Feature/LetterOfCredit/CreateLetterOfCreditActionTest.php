<?php

use App\Models\Company;
use Brick\Money\Money;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\Partner;
use Modules\Payment\Actions\LetterOfCredit\CreateLetterOfCreditAction;
use Modules\Payment\DataTransferObjects\LetterOfCredit\CreateLetterOfCreditDTO;
use Modules\Payment\Enums\LetterOfCredit\LCStatus;
use Modules\Payment\Enums\LetterOfCredit\LCType;
use Modules\Payment\Models\LetterOfCredit;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->vendor = Partner::factory()->create(['company_id' => $this->company->id]);
    $this->currency = $this->company->currency; // Use company's currency
});

it('creates a letter of credit with draft status', function () {
    $dto = new CreateLetterOfCreditDTO(
        company_id: $this->company->id,
        vendor_id: $this->vendor->id,
        issuing_bank_partner_id: null,
        currency_id: $this->currency->id,
        purchase_order_id: null,
        created_by_user_id: $this->user->id,
        amount: Money::of(100000, 'IQD'),
        amount_company_currency: Money::of(100000, 'IQD'),
        issue_date: now(),
        expiry_date: now()->addMonths(3),
        shipment_date: null,
        type: LCType::Import->value,
        incoterm: 'FOB',
        terms_and_conditions: 'Standard terms',
        notes: 'Test LC',
    );

    $action = app(CreateLetterOfCreditAction::class);
    $lc = $action->execute($dto, $this->user);

    expect($lc)->toBeInstanceOf(LetterOfCredit::class)
        ->and($lc->status)->toBe(LCStatus::Draft)
        ->and($lc->type)->toBe(LCType::Import)
        ->and($lc->vendor_id)->toBe($this->vendor->id)
        ->and($lc->amount->isEqualTo(Money::of(100000, 'IQD')))->toBeTrue()
        ->and($lc->balance->isEqualTo(Money::of(100000, 'IQD')))->toBeTrue()
        ->and($lc->utilized_amount->isZero())->toBeTrue()
        ->and($lc->lc_number)->not->toBeNull();
});

it('generates unique LC numbers', function () {
    $dto = new CreateLetterOfCreditDTO(
        company_id: $this->company->id,
        vendor_id: $this->vendor->id,
        issuing_bank_partner_id: null,
        currency_id: $this->currency->id,
        purchase_order_id: null,
        created_by_user_id: $this->user->id,
        amount: Money::of(50000, 'IQD'),
        amount_company_currency: Money::of(50000, 'IQD'),
        issue_date: now(),
        expiry_date: now()->addMonths(2),
        shipment_date: null,
        type: LCType::Import->value,
        incoterm: null,
        terms_and_conditions: null,
        notes: null,
    );

    $action = app(CreateLetterOfCreditAction::class);

    $lc1 = $action->execute($dto, $this->user);
    $lc2 = $action->execute($dto, $this->user);

    expect($lc1->lc_number)->not->toBe($lc2->lc_number);
});
