<?php

namespace Kezi\Accounting\Tests\Feature\Filament\Clusters\Accounting\Resources\VendorBills\Pages;

use Brick\Money\Money;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\EditVendorBill;
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

it('can register a partial payment via page action', function () {
    $vendor = Partner::factory()->for($this->company)->create(['type' => \Kezi\Foundation\Enums\Partners\PartnerType::Vendor]);
    $bill = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $vendor->id,
        'currency_id' => $this->currency->id,
        'status' => VendorBillStatus::Posted,
        'total_amount' => Money::of(500, $this->currency->code),
    ]);

    $livewire = Livewire::test(EditVendorBill::class, [
        'record' => $bill->id,
    ]);

    $livewire->mountAction('register_payment');

    $livewire->setActionData([
        'journal_id' => $this->journal->id,
        'payment_date' => now()->format('Y-m-d'),
        'currency_id' => $this->currency->id,
        'amount' => 200, // Partial amount
    ]);

    $livewire->assertHasNoActionErrors();
    $livewire->callMountedAction();

    $bill->refresh();

    expect($bill->payments)->toHaveCount(1)
        ->and($bill->payments->first()->amount->getAmount()->toFloat())->toBe(200.0)
        ->and($bill->status)->toBe(VendorBillStatus::Posted);
});
