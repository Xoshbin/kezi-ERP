<?php

namespace Tests\Traits;

use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Journal;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Inventory\Enums\Inventory\StockLocationType;
use Kezi\Inventory\Models\StockLocation;
use Tests\Builders\CompanyBuilder;

/**
 * @property Company $company
 * @property User $user
 * @property Account $inventoryAccount
 * @property Account $stockInputAccount
 * @property Account $cogsAccount
 * @property StockLocation $vendorLocation
 * @property StockLocation $stockLocation
 * @property StockLocation $adjustmentLocation
 * @property StockLocation $customerLocation
 * @property Partner $vendor
 * @property Currency $usdCurrency
 * @property Currency $eurCurrency
 * @property Journal $usdBankJournal
 * @property Currency $baseCurrency
 * @property Currency $foreignCurrency
 * @property float $exchangeRate
 *
 * @mixin \Tests\TestCase
 */
/**
 * @property \Kezi\Inventory\Models\StockLocation $stockLocation
 * @property \Kezi\Inventory\Models\StockLocation $warehouse
 * @property \Kezi\Inventory\Models\StockLocation $vendorLocation
 * @property \Kezi\Inventory\Models\StockLocation $customerLocation
 * @property \Kezi\Inventory\Models\StockLocation $adjustmentLocation
 * @property \Kezi\Accounting\Models\Account $stockInputAccount
 * @property \Kezi\Accounting\Models\Account $cogsAccount
 * @property \Kezi\Accounting\Models\Account $inventoryAccount
 * @property \Kezi\Foundation\Models\Partner $vendor
 * @property \Kezi\Foundation\Models\Partner $customer
 * @property \App\Models\Company $company
 * @property \App\Models\User $user
 * @property \Kezi\Product\Models\Product $product
 */
trait WithConfiguredCompany
{
    public function setUpWithConfiguredCompany(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Seed roles and permissions if they are missing
        if (\Spatie\Permission\Models\Permission::count() === 0 || \Spatie\Permission\Models\Role::count() === 0) {
            $this->seed(\Kezi\Foundation\Database\Seeders\RolesAndPermissionsSeeder::class);
        }

        $this->company = CompanyBuilder::new()
            ->withDefaultAccounts()
            ->withDefaultJournals()
            ->withDefaultStockLocations()
            ->withReconciliationEnabled()
            ->create();

        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company);

        // Set global team/company id for Spatie permissions
        setPermissionsTeamId($this->company->id);

        // Assign super_admin role to the test user so they can access everything
        // Since roles are now company-specific, we need to ensure the super_admin role exists for this company
        $superAdminRole = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'super_admin',
            'company_id' => $this->company->id,
        ]);

        // Grant all permissions to super_admin if it was just created
        if ($superAdminRole->wasRecentlyCreated) {
            $superAdminRole->givePermissionTo(\Spatie\Permission\Models\Permission::all());
        }

        $this->user->assignRole($superAdminRole);

        // Explicitly refresh user and force permission hydration
        $this->user->refresh();
        $this->user->unsetRelation('roles');
        $this->user->unsetRelation('permissions');

        // This access forces Spatie to hydrate its internal cache/state for this user instance
        // We ensure critical permissions exist to avoid mass failures in parallel tests
        foreach (['create_invoice', 'confirm_vendor_bill'] as $permission) {
            \Spatie\Permission\Models\Permission::findOrCreate($permission, 'web');
        }
        $this->user->hasPermissionTo('create_invoice');

        $this->actingAs($this->user);

        // Set the current panel to ensures URL generation and middleware work correctly
        \Filament\Facades\Filament::setTenant($this->company);
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('kezi'));

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
        /** @var Account $inventoryAccount */
        $inventoryAccount = \Kezi\Accounting\Models\Account::factory()->for($this->company)->create(['name' => 'Stock Valuation', 'type' => 'current_assets']);
        $this->inventoryAccount = $inventoryAccount;

        /** @var Account $stockInputAccount */
        $stockInputAccount = \Kezi\Accounting\Models\Account::factory()->for($this->company)->create(['name' => 'Stock Input', 'type' => 'current_liabilities']);
        $this->stockInputAccount = $stockInputAccount;

        /** @var Account $cogsAccount */
        $cogsAccount = \Kezi\Accounting\Models\Account::factory()->for($this->company)->create(['name' => 'Cost of Goods Sold', 'type' => 'expense']);
        $this->cogsAccount = $cogsAccount;

        // 2. Create the necessary physical locations
        /** @var StockLocation $vendorLocation */
        $vendorLocation = StockLocation::factory()->for($this->company)->create(['type' => StockLocationType::Vendor]);
        $this->vendorLocation = $vendorLocation;

        /** @var StockLocation $stockLocation */
        $stockLocation = StockLocation::factory()->for($this->company)->create(['type' => StockLocationType::Internal]);
        $this->stockLocation = $stockLocation;

        /** @var StockLocation $adjustmentLocation */
        $adjustmentLocation = StockLocation::factory()->for($this->company)->create(['type' => StockLocationType::InventoryAdjustment]);
        $this->adjustmentLocation = $adjustmentLocation;

        /** @var StockLocation $customerLocation */
        $customerLocation = StockLocation::factory()->for($this->company)->create(['type' => StockLocationType::Customer]);
        $this->customerLocation = $customerLocation;

        // 3. Associate accounts and locations with company defaults
        $this->company->update([
            'inventory_adjustment_account_id' => $inventoryAccount->id,
            'default_stock_input_account_id' => $stockInputAccount->id,
            'default_vendor_location_id' => $vendorLocation->id,
            'default_stock_location_id' => $stockLocation->id,
            'default_adjustment_location_id' => $adjustmentLocation->id,
        ]);

        /** @var Partner $vendor */
        $vendor = \Kezi\Foundation\Models\Partner::factory()->for($this->company)->create(['type' => \Kezi\Foundation\Enums\Partners\PartnerType::Vendor]);
        $this->vendor = $vendor;
    }

    /**
     * Seed stock for a product at a location
     */
    protected function seedStock(\Kezi\Product\Models\Product $product, \Kezi\Inventory\Models\StockLocation $location, float $quantity, ?int $lotId = null, ?int $serialId = null): void
    {
        app(\Kezi\Inventory\Services\Inventory\StockQuantService::class)->adjust(
            $product->company_id,
            $product->id,
            $location->id,
            $quantity,
            0,
            $lotId,
            $serialId
        );
    }
}
