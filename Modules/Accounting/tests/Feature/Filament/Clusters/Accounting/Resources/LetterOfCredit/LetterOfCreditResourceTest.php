<?php

use Modules\Accounting\Filament\Clusters\Accounting\Resources\LetterOfCredit\LetterOfCreditResource\Pages\CreateLetterOfCredit;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\LetterOfCredit\LetterOfCreditResource\Pages\EditLetterOfCredit;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\LetterOfCredit\LetterOfCreditResource\Pages\ListLetterOfCredits;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\Partner;
use Modules\Payment\Enums\LetterOfCredit\LCStatus;
use Modules\Payment\Enums\LetterOfCredit\LCType;
use Modules\Payment\Models\LetterOfCredit;
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
