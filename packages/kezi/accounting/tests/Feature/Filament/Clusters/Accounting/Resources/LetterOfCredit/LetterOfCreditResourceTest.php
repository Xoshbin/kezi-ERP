<?php

use Kezi\Accounting\Filament\Clusters\Accounting\Resources\LetterOfCredit\LetterOfCreditResource\Pages\CreateLetterOfCredit;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\LetterOfCredit\LetterOfCreditResource\Pages\EditLetterOfCredit;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\LetterOfCredit\LetterOfCreditResource\Pages\ListLetterOfCredits;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Payment\Enums\LetterOfCredit\LCStatus;
use Kezi\Payment\Enums\LetterOfCredit\LCType;
use Kezi\Payment\Models\LetterOfCredit;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    $this->vendor = Partner::factory()->create(['company_id' => $this->company->id]);
    $this->currency = $this->company->currency; // Use company's currency instead of creating new one
});

it('can render list page', function () {
    livewire(ListLetterOfCredits::class)
        ->assertSuccessful();
});

it('can list letter of credits', function () {
    $lcs = LetterOfCredit::factory()->count(3)->create([
        'company_id' => $this->company->id,
    ]);

    livewire(ListLetterOfCredits::class)
        ->assertCanSeeTableRecords($lcs);
});

it('can render create page', function () {
    livewire(CreateLetterOfCredit::class)
        ->assertSuccessful();
});

it('can create letter of credit', function () {
    livewire(CreateLetterOfCredit::class)
        ->fillForm([
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->currency->id,
            'amount' => 100000,
            'type' => LCType::Import->value,
            'issue_date' => now()->format('Y-m-d'),
            'expiry_date' => now()->addMonths(3)->format('Y-m-d'),
            'incoterm' => 'FOB',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('letter_of_credits', [
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'type' => LCType::Import->value,
        'status' => LCStatus::Draft->value,
    ]);
});

it('can render edit page', function () {
    $lc = LetterOfCredit::factory()->create([
        'company_id' => $this->company->id,
        'status' => LCStatus::Draft,
    ]);

    livewire(EditLetterOfCredit::class, ['record' => $lc->id])
        ->assertSuccessful();
});

it('can retrieve data from edit page', function () {
    $lc = LetterOfCredit::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
    ]);

    livewire(EditLetterOfCredit::class, ['record' => $lc->id])
        ->assertFormSet([
            'vendor_id' => $lc->vendor_id,
            'type' => $lc->type,
        ]);
});

it('can filter by status', function () {
    $draftLC = LetterOfCredit::factory()->create([
        'company_id' => $this->company->id,
        'status' => LCStatus::Draft,
    ]);

    $issuedLC = LetterOfCredit::factory()->create([
        'company_id' => $this->company->id,
        'status' => LCStatus::Issued,
    ]);

    livewire(ListLetterOfCredits::class)
        ->filterTable('status', LCStatus::Draft->value)
        ->assertCanSeeTableRecords([$draftLC])
        ->assertCanNotSeeTableRecords([$issuedLC]);
});

it('can search by LC number', function () {
    $lc1 = LetterOfCredit::factory()->create([
        'company_id' => $this->company->id,
        'lc_number' => 'LC-2024-001',
    ]);

    $lc2 = LetterOfCredit::factory()->create([
        'company_id' => $this->company->id,
        'lc_number' => 'LC-2024-002',
    ]);

    livewire(ListLetterOfCredits::class)
        ->searchTable('LC-2024-001')
        ->assertCanSeeTableRecords([$lc1])
        ->assertCanNotSeeTableRecords([$lc2]);
});

it('uses selected currency for amount', function () {
    $usd = Currency::factory()->create([
        'code' => 'USD',
        'is_active' => true,
    ]);

    \Kezi\Foundation\Models\CurrencyRate::factory()->create([
        'currency_id' => $usd->id,
        'company_id' => $this->company->id,
        'rate' => 1310,
        'effective_date' => now()->subDay(),
    ]);

    livewire(CreateLetterOfCredit::class)
        ->fillForm([
            'vendor_id' => $this->vendor->id,
            'currency_id' => $usd->id,
            'amount' => 100,
            'type' => LCType::Import->value,
            'issue_date' => now()->format('Y-m-d'),
            'expiry_date' => now()->addMonths(3)->format('Y-m-d'),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $lc = LetterOfCredit::latest()->first();

    expect($lc->amount->getCurrency()->getCurrencyCode())->toBe('USD');
});

it('converts amount to company currency', function () {
    // Company currency is usually IQD in tests from setUp, let's make sure we have a rate for USD
    $usd = Currency::factory()->create([
        'code' => 'USD',
        'is_active' => true,
        'decimal_places' => 2,
    ]);

    // Create a rate: 1 USD = 1310 IQD (example)
    // We need to check how rates are stored. Usually relative to base currency?
    // Let's assume standard way: 1 Unit of Foreign = X Units of Base (or vice versa).
    // Looking at CurrencyConverterService:
    // convertToBaseCurrency uses getExchangeRate.
    // CurrencyRate::getRateForDate($currency->id, $date, $company->id)
    // "The rate represents how much of the base currency equals 1 unit of the foreign currency."
    // So if Base is IQD, Foreign is USD. Rate should be 1310.

    \Kezi\Foundation\Models\CurrencyRate::factory()->create([
        'currency_id' => $usd->id,
        'company_id' => $this->company->id,
        'rate' => 1310, // 1 USD = 1310 IQD
        'effective_date' => now()->subDay(),
    ]);

    livewire(CreateLetterOfCredit::class)
        ->fillForm([
            'vendor_id' => $this->vendor->id,
            'currency_id' => $usd->id,
            'amount' => 100, // 100 USD
            'type' => LCType::Import->value,
            'issue_date' => now()->format('Y-m-d'),
            'expiry_date' => now()->addMonths(3)->format('Y-m-d'),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $lc = LetterOfCredit::latest()->first();

    // 100 USD * 1310 = 131000 IQD
    expect($lc->amount_company_currency->getAmount()->toFloat())->toBe(131000.0)
        ->and($lc->amount_company_currency->getCurrency()->getCurrencyCode())->toBe($this->company->currency->code);
});
