<?php

namespace Tests\Feature\Observers;

use App\Actions\Purchases\CreateVendorBillAction;
use App\DataTransferObjects\Purchases\CreateVendorBillDTO;
use App\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use App\Enums\Accounting\JournalType;
use App\Enums\Inventory\StockLocationType;
use App\Enums\Products\ProductType;
use App\Models\Account;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Journal;
use App\Models\Partner;
use App\Models\Product;
use App\Models\StockLocation;
use App\Models\User;
use App\Services\VendorBillService;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    // Create currencies with proper exchange rates
    $this->iqd = Currency::firstOrCreate(
        ['code' => 'IQD'],
        [
            'name' => 'Iraqi Dinar',
            'symbol' => 'IQD',
            'exchange_rate' => 1.0, // Base currency
            'is_active' => true,
            'decimal_places' => 3
        ]
    );

    $this->usd = Currency::firstOrCreate(
        ['code' => 'USD'],
        [
            'name' => 'US Dollar',
            'symbol' => '$',
            'exchange_rate' => 1460.0, // 1 USD = 1460 IQD
            'is_active' => true,
            'decimal_places' => 2
        ]
    );
});

test('updates product cost in base currency when vendor bill is in foreign currency', function () {
    // Setup: Company with IQD base currency
    $company = Company::factory()->create([
        'currency_id' => $this->iqd->id,
        'name' => 'Test Company IQD'
    ]);

    $user = User::factory()->create();

    // Setup accounts
    $inventoryAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Inventory',
        'code' => '1300',
        'type' => 'current_assets'
    ]);

    $stockInputAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Stock Input',
        'code' => '5100',
        'type' => 'expense'
    ]);

    $apAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Accounts Payable',
        'code' => '2100',
        'type' => 'payable'
    ]);

    // Create journals and stock locations
    $purchaseJournal = Journal::factory()->create([
        'company_id' => $company->id,
        'name' => 'Purchase Journal',
        'type' => JournalType::Purchase,
        'short_code' => 'BILL',
        'currency_id' => $company->currency_id,
        'default_debit_account_id' => $stockInputAccount->id,
        'default_credit_account_id' => $apAccount->id,
    ]);

    $vendorLocation = StockLocation::factory()->create([
        'company_id' => $company->id,
        'name' => 'Vendor Location',
        'type' => StockLocationType::Vendor,
        'is_active' => true,
    ]);

    $stockLocation = StockLocation::factory()->create([
        'company_id' => $company->id,
        'name' => 'Main Warehouse',
        'type' => StockLocationType::Internal,
        'is_active' => true,
    ]);

    // Configure company
    $company->update([
        'default_accounts_payable_id' => $apAccount->id,
        'default_purchase_journal_id' => $purchaseJournal->id,
        'default_vendor_location_id' => $vendorLocation->id,
        'default_stock_location_id' => $stockLocation->id,
    ]);

    // Create vendor
    $vendor = Partner::factory()->vendor()->create([
        'company_id' => $company->id,
        'name' => 'USD Vendor'
    ]);

    // Create storable product with initial cost in base currency (IQD)
    $product = Product::factory()->create([
        'company_id' => $company->id,
        'name' => 'Test Product',
        'type' => ProductType::Storable,
        'unit_price' => Money::of(100, 'USD'),
        'average_cost' => Money::of(100000, 'IQD'), // Initial cost in base currency
        'quantity_on_hand' => 1, // Initial quantity
        'default_inventory_account_id' => $inventoryAccount->id,
        'default_stock_input_account_id' => $stockInputAccount->id,
    ]);

    // Create vendor bill in USD
    $vendorBillDTO = new CreateVendorBillDTO(
        company_id: $company->id,
        vendor_id: $vendor->id,
        currency_id: $this->usd->id, // Bill in USD
        bill_reference: 'BILL-USD-001',
        bill_date: now()->format('Y-m-d'),
        accounting_date: now()->format('Y-m-d'),
        due_date: now()->addDays(30)->format('Y-m-d'),
        lines: [
            new CreateVendorBillLineDTO(
                product_id: $product->id,
                description: 'Test Product - USD Purchase',
                quantity: 1,
                unit_price: Money::of(100, 'USD'), // $100 USD
                expense_account_id: $stockInputAccount->id,
                tax_id: null,
                analytic_account_id: null,
                currency: 'USD'
            )
        ],
        created_by_user_id: $user->id
    );

    $vendorBill = app(CreateVendorBillAction::class)->execute($vendorBillDTO);

    // Confirm/post the vendor bill to trigger the observer
    app(VendorBillService::class)->confirm($vendorBill, $user);

    // Reload product to get updated cost
    $product->refresh();

    // EXPECTED BEHAVIOR: Product cost should be updated in company base currency (IQD)
    expect($product->average_cost->getCurrency()->getCurrencyCode())->toBe('IQD', 'Product cost must be in company base currency');

    // Calculate expected average cost:
    // Initial: 1 unit @ 100,000 IQD = 100,000 IQD
    // Purchase: 1 unit @ $100 USD = 146,000 IQD (using exchange rate 1460)
    // New average: (100,000 + 146,000) / 2 = 123,000 IQD
    $expectedAverageCost = Money::of(123000, 'IQD');
    
    expect($product->average_cost->isEqualTo($expectedAverageCost))->toBeTrue(
        'Product average cost should be calculated correctly in base currency. Expected: ' . $expectedAverageCost->getAmount() . ' IQD, Got: ' . $product->average_cost->getAmount() . ' ' . $product->average_cost->getCurrency()->getCurrencyCode()
    );

    // Verify quantity is updated
    expect($product->quantity_on_hand)->toBe(2, 'Product quantity should be increased by 1');
});

test('handles same currency vendor bill correctly in observer', function () {
    // Setup: Company with IQD base currency, vendor bill also in IQD
    $company = Company::factory()->create([
        'currency_id' => $this->iqd->id,
        'name' => 'Test Company IQD'
    ]);

    $user = User::factory()->create();

    // Setup minimal required accounts and locations
    $inventoryAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'current_assets']);
    $stockInputAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'expense']);
    $apAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'payable']);
    
    $purchaseJournal = Journal::factory()->create([
        'company_id' => $company->id,
        'type' => JournalType::Purchase,
        'currency_id' => $company->currency_id,
    ]);

    $vendorLocation = StockLocation::factory()->create(['company_id' => $company->id, 'type' => StockLocationType::Vendor]);
    $stockLocation = StockLocation::factory()->create(['company_id' => $company->id, 'type' => StockLocationType::Internal]);

    $company->update([
        'default_accounts_payable_id' => $apAccount->id,
        'default_purchase_journal_id' => $purchaseJournal->id,
        'default_vendor_location_id' => $vendorLocation->id,
        'default_stock_location_id' => $stockLocation->id,
    ]);

    $vendor = Partner::factory()->vendor()->create(['company_id' => $company->id]);

    // Create product with initial cost in IQD
    $product = Product::factory()->create([
        'company_id' => $company->id,
        'type' => ProductType::Storable,
        'average_cost' => Money::of(100000, 'IQD'),
        'quantity_on_hand' => 1,
        'default_inventory_account_id' => $inventoryAccount->id,
        'default_stock_input_account_id' => $stockInputAccount->id,
    ]);

    // Create vendor bill in IQD (same currency)
    $vendorBillDTO = new CreateVendorBillDTO(
        company_id: $company->id,
        vendor_id: $vendor->id,
        currency_id: $this->iqd->id, // Same currency as company
        bill_reference: 'BILL-IQD-001',
        bill_date: now()->format('Y-m-d'),
        accounting_date: now()->format('Y-m-d'),
        due_date: now()->addDays(30)->format('Y-m-d'),
        lines: [
            new CreateVendorBillLineDTO(
                product_id: $product->id,
                description: 'Test Product - IQD Purchase',
                quantity: 1,
                unit_price: Money::of(146000, 'IQD'), // 146,000 IQD
                expense_account_id: $stockInputAccount->id,
                tax_id: null,
                analytic_account_id: null,
                currency: 'IQD'
            )
        ],
        created_by_user_id: $user->id
    );

    $vendorBill = app(CreateVendorBillAction::class)->execute($vendorBillDTO);
    app(VendorBillService::class)->confirm($vendorBill, $user);

    $product->refresh();

    // Should remain in IQD with correct calculation
    expect($product->average_cost->getCurrency()->getCurrencyCode())->toBe('IQD');
    
    // Expected: (100,000 + 146,000) / 2 = 123,000 IQD
    $expectedAverageCost = Money::of(123000, 'IQD');
    expect($product->average_cost->isEqualTo($expectedAverageCost))->toBeTrue();
    expect($product->quantity_on_hand)->toBe(2);
});
