<?php

namespace Kezi\Accounting\Tests\Feature\Filament\Clusters\Accounting\Resources\VendorBills\RelationManagers;

use Brick\Money\Money;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\EditVendorBill;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\RelationManagers\PaymentsRelationManager;
use Kezi\Accounting\Models\Journal;
use Kezi\Foundation\Models\Partner;
use Kezi\Purchase\Enums\Purchases\VendorBillStatus;
use Kezi\Purchase\Models\VendorBill;
use Livewire\Livewire;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);
    Filament::setTenant($this->company);

    $this->journal = Journal::factory()->for($this->company)->create(['type' => 'bank']);
    $this->currency = $this->company->currency;
});

it('can see register payment action in relation manager', function () {
    $vendor = Partner::factory()->for($this->company)->create(['type' => \Kezi\Foundation\Enums\Partners\PartnerType::Vendor]);
    $bill = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $vendor->id,
        'currency_id' => $this->currency->id,
        'status' => VendorBillStatus::Posted,
        'total_amount' => Money::of(500, $this->currency->code),
    ]);

    $livewire = Livewire::test(PaymentsRelationManager::class, [
        'ownerRecord' => $bill,
        'pageClass' => EditVendorBill::class,
    ]);

    // Using a more direct check if the action exists and is visible
    $headerActions = $livewire->instance()->getTable()->getHeaderActions();

    $action = collect($headerActions)->first(fn ($a) => $a->getName() === 'register_payment');

    expect($action)->not->toBeNull();

    // Explicitly set the record to ensure visibility logic check works
    $action->record($bill);

    expect($action->isVisible())->toBeTrue();
});
