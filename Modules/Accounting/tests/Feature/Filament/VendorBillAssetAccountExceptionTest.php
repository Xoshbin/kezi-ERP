<?php

namespace Modules\Accounting\Tests\Feature\Filament;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\EditVendorBill;
use Modules\Accounting\Models\Account;
use Modules\Foundation\Models\Partner;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Models\VendorBill;
use Modules\Purchase\Models\VendorBillLine;
use Tests\Traits\WithConfiguredCompany;

/**
 * Regression test for LogicException: The relationship [assetAccount] does not exist on the model [Modules\Purchase\Models\VendorBill]
 *
 * This occurs when creating an AssetCategory from within a VendorBill line item's advanced settings on the Edit page.
 * The issue was that the nested relation creation form was incorrectly attempting to resolve relationships
 * against the VendorBill model instead of the AssetCategory model.
 */
uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);
    Filament::setTenant($this->company);

    $this->vendor = Partner::factory()->vendor()->create(['company_id' => $this->company->id]);

    // We need some accounts for the options to populate
    $this->account = Account::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Asset Test Account',
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
    ]);
});

test('it can render asset category creation form without logic exception on edit page', function () {
    $lw = Livewire::test(EditVendorBill::class, [
        'record' => $this->bill->getKey(),
    ]);

    $state = $lw->get('data.lines');
    $itemKey = array_keys($state)[0] ?? null;

    if ($itemKey === null) {
        throw new \Exception('No items found in lines repeater');
    }

    // Mount nested actions properly
    // advanced_settings (repeater action) -> createOption (select action)
    $lw->mountFormComponentAction(
        ['lines', "lines.{$itemKey}.asset_category_id"],
        ['advanced_settings', 'createOption'],
        [
            'advanced_settings' => ['item' => (string) $itemKey],
            'createOption' => [],
        ]
    );

    $lw->assertSuccessful();
});
