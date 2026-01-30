<?php

namespace Modules\Accounting\Tests\Feature\Filament;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\EditVendorBill;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\AssetCategory;
use Modules\Foundation\Models\Partner;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Models\VendorBill;
use Modules\Purchase\Models\VendorBillLine;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);
    Filament::setTenant($this->company);

    $this->vendor = Partner::factory()->vendor()->create(['company_id' => $this->company->id]);
    $this->account = Account::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Expense Account',
    ]);

    // Create an Asset Category manually
    $this->assetCategory = AssetCategory::create([
        'company_id' => $this->company->id,
        'name' => 'Test Asset Category',
        'asset_account_id' => $this->account->id,
        'accumulated_depreciation_account_id' => $this->account->id,
        'depreciation_expense_account_id' => $this->account->id,
        'depreciation_method' => \Modules\Accounting\Enums\Assets\DepreciationMethod::StraightLine,
        'useful_life_years' => 5,
        'is_active' => true,
    ]);

    $this->bill = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'status' => VendorBillStatus::Draft,
    ]);

    // Add a line to the bill
    $this->line = VendorBillLine::factory()->create([
        'vendor_bill_id' => $this->bill->id,
        'company_id' => $this->company->id,
        'expense_account_id' => $this->account->id,
        'description' => 'Test Line Item',
    ]);
});

test('can attach asset category to vendor bill line via advanced settings', function () {
    $lw = Livewire::test(EditVendorBill::class, [
        'record' => $this->bill->getKey(),
    ]);

    $state = $lw->get('data.lines');
    $itemKey = array_keys($state)[0];

    // Set the asset category directly in the form data
    // This simulates the result of the advanced_settings action updating the hidden field
    $lw->set("data.lines.{$itemKey}.asset_category_id", $this->assetCategory->id);

    // Save the Vendor Bill
    $lw->call('save')
        ->assertHasNoErrors();

    // Verify DB persistence
    $this->bill->refresh();
    $newLine = $this->bill->lines->first();
    expect($newLine->asset_category_id)->toBe($this->assetCategory->id);

    // Verify it loads correctly on refresh
    // This is where we expect failure due to mutateFormDataBeforeFill bug
    $lw2 = Livewire::test(EditVendorBill::class, [
        'record' => $this->bill->getKey(),
    ]);

    $state2 = $lw2->get('data.lines');
    $firstItem = reset($state2);

    expect($firstItem)->toHaveKey('asset_category_id');
    expect($firstItem['asset_category_id'])->toBe($this->assetCategory->id);
});
