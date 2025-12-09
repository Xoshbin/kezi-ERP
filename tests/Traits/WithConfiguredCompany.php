<?php

namespace Tests\Traits;

use App\Models\User;
use Filament\Facades\Filament;
use Modules\Inventory\Enums\Inventory\StockLocationType;
use Modules\Inventory\Models\StockLocation;
use Tests\Builders\CompanyBuilder;

trait WithConfiguredCompany
{
    protected function setupWithConfiguredCompany(): void
    {
        $this->company = CompanyBuilder::new()
            ->withDefaultAccounts()
            ->withDefaultJournals()
            ->withDefaultStockLocations()
            ->withReconciliationEnabled()
            ->create();

        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company);
        $this->actingAs($this->user);

        // Set up Filament tenant context
        Filament::setTenant($this->company);
    }

    /**
     * NEW: An additive method to set up the specific environment for inventory tests.
     * This encapsulates the duplicated logic from the inventory test files.
     */
    protected function setupInventoryTestEnvironment(): void
    {
        // 1. Create inventory-specific GL accounts
        $this->inventoryAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create(['name' => 'Stock Valuation', 'type' => 'current_assets']);
        $this->stockInputAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create(['name' => 'Stock Input', 'type' => 'current_liabilities']);
        $this->cogsAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create(['name' => 'Cost of Goods Sold', 'type' => 'expense']);

        // 2. Create the necessary physical locations
        $this->vendorLocation = StockLocation::factory()->for($this->company)->create(['type' => StockLocationType::Vendor]);
        $this->stockLocation = StockLocation::factory()->for($this->company)->create(['type' => StockLocationType::Internal]);
        $this->adjustmentLocation = StockLocation::factory()->for($this->company)->create(['type' => StockLocationType::InventoryAdjustment]);
        $this->customerLocation = StockLocation::factory()->for($this->company)->create(['type' => StockLocationType::Customer]);

        // 3. Associate accounts and locations with company defaults
        $this->company->update([
            'default_inventory_account_id' => $this->inventoryAccount->id,
            'default_stock_input_account_id' => $this->stockInputAccount->id,
            'default_vendor_location_id' => $this->vendorLocation->id,
            'default_stock_location_id' => $this->stockLocation->id,
            'default_adjustment_location_id' => $this->adjustmentLocation->id,
        ]);

        // 4. Create a default vendor for the tests
        $this->vendor = \Modules\Foundation\Models\Partner::factory()->for($this->company)->create(['type' => \Modules\Foundation\Enums\Partners\PartnerType::Vendor]);
    }
}
