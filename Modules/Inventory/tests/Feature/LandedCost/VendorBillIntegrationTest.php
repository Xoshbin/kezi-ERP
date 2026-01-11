<?php

use Modules\Inventory\Filament\Resources\LandedCostResource\Pages\CreateLandedCost;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Models\VendorBill;

use function Pest\Livewire\livewire;

// uses(\Tests\TestCase::class);

it('can pre-fill landed cost from vendor bill', function () {
    // 1. Setup Data
    $company = \App\Models\Company::factory()->create();
    $currency = \Modules\Foundation\Models\Currency::factory()->create(['code' => 'USD']);
    $company->currency_id = $currency->id;
    $company->save();

    $user = \App\Models\User::factory()->create();
    $this->actingAs($user);

    $vendor = \Modules\Foundation\Models\Partner::factory()->create(['company_id' => $company->id]);

    // Create a posted vendor bill
    $bill = VendorBill::create([
        'company_id' => $company->id,
        'vendor_id' => $vendor->id,
        'currency_id' => $currency->id,
        'bill_date' => now(),
        'accounting_date' => now(),
        'bill_reference' => 'BILL-2026-001',
        'status' => VendorBillStatus::Posted,
        'total_amount' => \Brick\Money\Money::of(150, 'USD'),
        'total_tax' => \Brick\Money\Money::of(0, 'USD'),
        'exchange_rate_at_creation' => 1.0,
    ]);

    // 2. Simulate visiting Create page with query param
    // We can't easily set query params for Livewire test in Pest without full HTTP request,
    // but we can manually merge into request since Livewire uses request() helper.
    request()->merge(['vendor_bill_id' => $bill->id]);

    \Livewire\Livewire::component('modules.inventory.filament.resources.landed-cost-resource.pages.create-landed-cost', CreateLandedCost::class);

    livewire(CreateLandedCost::class)
        ->assertFormSet([
            'vendor_bill_id' => $bill->id,
            'amount_total' => 150.00,
            'company_id' => $company->id,
            'description' => "Landed Cost - {$bill->bill_reference}",
        ]);
});
