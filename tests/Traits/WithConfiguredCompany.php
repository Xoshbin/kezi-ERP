<?php

namespace Tests\Traits;

use App\Models\User;
use App\Models\Account;
use App\Models\Partner;
use App\Models\StockLocation;
use Filament\Facades\Filament;
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

        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company);
        $this->actingAs($this->user);

        // Set up Filament tenant context
        \Filament\Facades\Filament::setTenant($this->company);
    }

    /**
     * NEW: An additive method to set up the specific environment for inventory tests.
     * This encapsulates the duplicated logic from the inventory test files.
     */
    protected function setupInventoryTestEnvironment(): void
    {
        // 1. Create inventory-specific GL accounts
        $this->inventoryAccount = Account::factory()->for($this->company)->create(['name' => 'Stock Valuation', 'type' => 'current_assets']);
        $this->stockInputAccount = Account::factory()->for($this->company)->create(['name' => 'Stock Input', 'type' => 'current_liabilities']);

        // 2. Create the necessary physical locations
        $this->vendorLocation = StockLocation::factory()->for($this->company)->create(['type' => StockLocationType::Vendor]);
        $this->stockLocation = StockLocation::factory()->for($this->company)->create(['type' => StockLocationType::Internal]);

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
