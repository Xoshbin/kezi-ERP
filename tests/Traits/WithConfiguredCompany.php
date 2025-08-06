<?php

namespace Tests\Traits;

use App\Models\User;
use App\Models\Account;
use App\Models\Partner;
use App\Models\StockLocation;
use Tests\Builders\CompanyBuilder;
use App\Enums\Partners\PartnerType;
use App\Enums\Inventory\StockLocationType;

trait WithConfiguredCompany
{
    protected function setupWithConfiguredCompany(): void
    {
        $this->company = CompanyBuilder::new()
            ->withDefaultAccounts()
            ->withDefaultJournals()
            ->withDefaultStockLocations()
            ->create();

        $this->user = User::factory()->for($this->company)->create();
        $this->actingAs($this->user);
    }

    /**
     * NEW: An additive method to set up the specific environment for inventory tests.
     * This encapsulates the duplicated logic from the inventory test files.
     */
    protected function setupInventoryTestEnvironment(): void
    {
        // 1. Create inventory-specific GL accounts
        $this->inventoryAccount = Account::factory()->for($this->company)->create(['name' => 'Stock Valuation', 'type' => 'asset']);
        $this->stockInputAccount = Account::factory()->for($this->company)->create(['name' => 'Stock Input', 'type' => 'liability']);

        // 2. Create the necessary physical locations
        $this->vendorLocation = StockLocation::factory()->for($this->company)->create(['type' => StockLocationType::VENDOR]);
        $this->stockLocation = StockLocation::factory()->for($this->company)->create(['type' => StockLocationType::INTERNAL]);

        // 3. Associate accounts and locations with company defaults
        $this->company->update([
            'default_inventory_account_id' => $this->inventoryAccount->id,
            'default_stock_input_account_id' => $this->stockInputAccount->id,
            'default_vendor_location_id' => $this->vendorLocation->id,
            'default_stock_location_id' => $this->stockLocation->id,
        ]);

        // 4. Create a default vendor for the tests
        $this->vendor = Partner::factory()->for($this->company)->create(['type' => PartnerType::Vendor]);
    }
}
