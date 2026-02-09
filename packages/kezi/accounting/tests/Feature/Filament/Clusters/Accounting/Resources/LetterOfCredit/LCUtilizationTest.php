<?php

use Kezi\Accounting\Filament\Clusters\Accounting\Resources\LetterOfCredit\LetterOfCreditResource\RelationManagers\UtilizationsRelationManager;
use Kezi\Foundation\Models\Partner;
use Kezi\Payment\Enums\LetterOfCredit\LCStatus;
use Kezi\Payment\Models\LetterOfCredit;
use Kezi\Purchase\Enums\Purchases\VendorBillStatus;
use Kezi\Purchase\Models\VendorBill;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    $this->vendor = Partner::factory()->create(['company_id' => $this->company->id]);
    $this->currency = $this->company->currency;

    $this->lc = LetterOfCredit::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->currency->id,
        'amount' => 10000,
        'balance' => 10000,
        'status' => LCStatus::Issued,
        'issue_date' => now(),
        'expiry_date' => now()->addYear(),
    ]);
});

it('can render utilizations relation manager', function () {
    livewire(UtilizationsRelationManager::class, [
        'ownerRecord' => $this->lc,
        'pageClass' => \Kezi\Accounting\Filament\Clusters\Accounting\Resources\LetterOfCredit\LetterOfCreditResource\Pages\EditLetterOfCredit::class,
    ])
        ->assertSuccessful();
});

it('can create utilization against a vendor bill', function () {
    $bill = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->currency->id,
        'status' => VendorBillStatus::Posted,
        'total_amount' => 5000,
    ]);

    livewire(UtilizationsRelationManager::class, [
        'ownerRecord' => $this->lc,
        'pageClass' => \Kezi\Accounting\Filament\Clusters\Accounting\Resources\LetterOfCredit\LetterOfCreditResource\Pages\EditLetterOfCredit::class,
    ])
        ->mountTableAction('create')
        ->setTableActionData([
            'vendor_bill_id' => $bill->id,
            'utilized_amount' => 5000,
            'utilization_date' => now()->format('Y-m-d'),
        ])
        ->callMountedTableAction()
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseHas('lc_utilizations', [
        'letter_of_credit_id' => $this->lc->id,
        'vendor_bill_id' => $bill->id,
        'utilized_amount' => 5000000, // stored in minor units (5000 * 1000 for IQD)
    ]);

    // Verify LC balance updated
    $this->lc->refresh();
    expect($this->lc->utilized_amount->getAmount()->toFloat())->toBe(5000.0)
        ->and($this->lc->balance->getAmount()->toFloat())->toBe(5000.0)
        ->and($this->lc->status)->toBe(LCStatus::PartiallyUtilized);
});

it('cannot utilize more than LC balance', function () {
    $bill = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->currency->id,
        'status' => VendorBillStatus::Posted,
        'total_amount' => 15000,
    ]);

    livewire(UtilizationsRelationManager::class, [
        'ownerRecord' => $this->lc,
        'pageClass' => \Kezi\Accounting\Filament\Clusters\Accounting\Resources\LetterOfCredit\LetterOfCreditResource\Pages\EditLetterOfCredit::class,
    ])
        ->mountTableAction('create')
        ->setTableActionData([
            'vendor_bill_id' => $bill->id,
            'utilized_amount' => 12000, // more than LC amount of 10000
            'utilization_date' => now()->format('Y-m-d'),
        ])
        ->callMountedTableAction()
        ->assertHasTableActionErrors(['utilized_amount']);
});
