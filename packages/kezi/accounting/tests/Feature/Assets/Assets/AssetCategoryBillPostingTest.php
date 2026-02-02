<?php

namespace Kezi\Accounting\Tests\Feature\Assets;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Enums\Assets\DepreciationMethod;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\AssetCategory;
use Kezi\Foundation\Models\Partner;
use Kezi\Purchase\Actions\Purchases\CreateVendorBillAction;
use Kezi\Purchase\DataTransferObjects\Purchases\CreateVendorBillDTO;
use Kezi\Purchase\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use Kezi\Purchase\Models\VendorBill;
use Kezi\Purchase\Services\VendorBillService;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

it('creates asset and posts Dr Asset / Cr AP for asset category bill lines', function () {
    $this->setupWithConfiguredCompany();
    $this->vendor = Partner::factory()->for($this->company)->vendor()->create();

    // Accounts for the asset category
    $assetAccount = Account::factory()->for($this->company)->create([
        'name' => ['en' => 'Office Equipment'],
        'type' => 'fixed_assets',
    ]);
    $accumDepAccount = Account::factory()->for($this->company)->create([
        'name' => ['en' => 'Accumulated Depreciation'],
        'type' => 'non_current_assets',
    ]);
    $depExpenseAccount = Account::factory()->for($this->company)->create([
        'name' => ['en' => 'Depreciation Expense'],
        'type' => 'depreciation',
    ]);

    $category = AssetCategory::create([
        'company_id' => $this->company->id,
        'name' => 'IT Equipment',
        'asset_account_id' => $assetAccount->id,
        'accumulated_depreciation_account_id' => $accumDepAccount->id,
        'depreciation_expense_account_id' => $depExpenseAccount->id,
        'depreciation_method' => DepreciationMethod::StraightLine,
        'useful_life_years' => 5,
        'salvage_value_default' => 0,
    ]);

    $vendorBillDTO = new CreateVendorBillDTO(
        company_id: $this->company->id,
        vendor_id: $this->vendor->id,
        currency_id: $this->company->currency_id,
        bill_reference: 'ASSET-CAT-001',
        bill_date: now()->toDateString(),
        accounting_date: now()->toDateString(),
        due_date: now()->addDays(30)->toDateString(),
        lines: [
            new CreateVendorBillLineDTO(
                product_id: null,
                description: 'Laptop',
                quantity: 1,
                unit_price: Money::of(1200, $this->company->currency->code),
                expense_account_id: $assetAccount->id,
                tax_id: null,
                analytic_account_id: null,
                asset_category_id: $category->id,
            ),
        ],
        created_by_user_id: $this->user->id,
    );

    $vendorBill = app(CreateVendorBillAction::class)->execute($vendorBillDTO);
    app(VendorBillService::class)->post($vendorBill, $this->user);

    $journalEntry = $vendorBill->refresh()->journalEntry;
    expect($journalEntry)->not->toBeNull();

    $amount = Money::of(1200, $this->company->currency->code)->getMinorAmount()->toInt();

    // Dr Asset
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $assetAccount->id,
        'debit' => $amount,
        'credit' => 0,
    ]);

    // Cr AP
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->company->default_accounts_payable_id,
        'debit' => 0,
        'credit' => $amount,
    ]);

    // Asset record created and linked to bill
    $this->assertDatabaseHas('assets', [
        'company_id' => $this->company->id,
        'name' => 'Laptop',
        'purchase_value' => $amount,
        'asset_account_id' => $assetAccount->id,
        'accumulated_depreciation_account_id' => $accumDepAccount->id,
        'depreciation_expense_account_id' => $depExpenseAccount->id,
        'source_type' => VendorBill::class,
        'source_id' => $vendorBill->id,
    ]);
});

it('fails to post if asset category account is invalid', function () {
    $this->setupWithConfiguredCompany();
    $this->vendor = Partner::factory()->for($this->company)->vendor()->create();

    // Create category but then delete one of the accounts or create with invalid one
    // Since we can't create with invalid FK constraint easily in test without disabling checks,
    // we'll simulate the runtime exception by manipulating the category AFTER creation if needed,
    // or relying on the validation logic in the posting preview.

    // However, the BuildVendorBillPostingPreviewAction checks for asset_category_invalid
    // processing fails if CreateAssetFromVendorBillListener tries to use it.

    // Actually, let's test the specific validation failure in VendorBillService::validateVendorBillForPosting
    // by mocking a scenario or just relying on the fact that if we delete the category, it should fail.

    // Better test: Ensure that if the category exists but the account is somehow null (e.g. data corruption or optional FKs),
    // the system handles it. But database constraints usually prevent this.

    // Let's test the "Invalid asset category on bill line" exception from CreateJournalEntryForVendorBillAction
    // by triggering a case where category ID is present on line but DB record is gone.

    $assetAccount = Account::factory()->for($this->company)->create(['type' => 'fixed_assets']);
    $category = AssetCategory::create([
        'company_id' => $this->company->id,
        'name' => 'Ghost Category',
        'asset_account_id' => $assetAccount->id,
        'accumulated_depreciation_account_id' => $assetAccount->id, // Dummy
        'depreciation_expense_account_id' => $assetAccount->id, // Dummy
        'depreciation_method' => DepreciationMethod::StraightLine,
        'useful_life_years' => 5,
    ]);

    $vendorBillDTO = new CreateVendorBillDTO(
        company_id: $this->company->id,
        vendor_id: $this->vendor->id,
        currency_id: $this->company->currency_id,
        bill_reference: 'FAIL-TEST',
        bill_date: now()->toDateString(),
        accounting_date: now()->toDateString(),
        due_date: now()->addDays(30)->toDateString(),
        lines: [
            new CreateVendorBillLineDTO(
                product_id: null,
                description: 'Ghost Asset',
                quantity: 1,
                unit_price: Money::of(1000, $this->company->currency->code),
                expense_account_id: $assetAccount->id,
                tax_id: null,
                analytic_account_id: null,
                asset_category_id: $category->id,
            ),
        ],
        created_by_user_id: $this->user->id,
    );

    $vendorBill = app(CreateVendorBillAction::class)->execute($vendorBillDTO);

    // Forcefully delete category to trigger "category not found" logic during posting check
    $category->delete();

    expect(fn () => app(VendorBillService::class)->post($vendorBill, $this->user))
        ->toThrow(\RuntimeException::class);
});

it('converts asset purchase value to company currency', function () {
    $this->setupWithConfiguredCompany();
    // Base currency is IQD (from WithConfiguredCompany trait)

    // Create USD Currency
    $usd = \Kezi\Foundation\Models\Currency::factory()->createSafely([
        'code' => 'USD',
        'name' => 'US Dollar',
        'symbol' => '$',
    ]);

    $exchangeRate = 1450.0;

    $this->vendor = Partner::factory()->for($this->company)->vendor()->create();

    $assetAccount = Account::factory()->for($this->company)->create(['type' => 'fixed_assets']);
    $accumDepAccount = Account::factory()->for($this->company)->create([
        'name' => ['en' => 'Accumulated Depreciation'],
        'type' => 'non_current_assets',
    ]);
    $depExpenseAccount = Account::factory()->for($this->company)->create([
        'name' => ['en' => 'Depreciation Expense'],
        'type' => 'depreciation',
    ]);

    $category = AssetCategory::create([
        'company_id' => $this->company->id,
        'name' => 'Imported Machine',
        'asset_account_id' => $assetAccount->id,
        'accumulated_depreciation_account_id' => $accumDepAccount->id,
        'depreciation_expense_account_id' => $depExpenseAccount->id,
        'depreciation_method' => DepreciationMethod::StraightLine,
        'useful_life_years' => 5,
    ]);

    // Bill in USD: 1000 USD
    $vendorBillDTO = new CreateVendorBillDTO(
        company_id: $this->company->id,
        vendor_id: $this->vendor->id,
        currency_id: $usd->id,
        bill_reference: 'USD-ASSET-001',
        bill_date: now()->toDateString(),
        accounting_date: now()->toDateString(),
        due_date: now()->addDays(30)->toDateString(),
        lines: [
            new CreateVendorBillLineDTO(
                product_id: null,
                description: 'Imported Machine',
                quantity: 1,
                unit_price: Money::of(1000, 'USD'), // 1000.00 USD (minor: 100000) - wait, check USD scale usually 2
                expense_account_id: $assetAccount->id,
                tax_id: null,
                analytic_account_id: null,
                asset_category_id: $category->id,
            ),
        ],
        created_by_user_id: $this->user->id,
    );

    // We need to manually set the exchange rate on the bill because DTO might not take it directly or
    // it's handled via CurrencyConverterService mock.
    // The service looks for exchange_rate_at_creation on the bill model.

    $vendorBill = app(CreateVendorBillAction::class)->execute($vendorBillDTO);
    $vendorBill->update(['exchange_rate_at_creation' => $exchangeRate]);

    // Mock Currency Converter Service? Or rely on the logic in VendorBillService
    // VendorBillService::processMultiCurrencyAmounts uses existing exchange rate if set.

    app(VendorBillService::class)->post($vendorBill, $this->user);

    // Expected IQD amount: 1000 * 1450 = 1,450,000
    // IQD has 0 scale/minor units usually, verify based on company currency config
    // Checking setupWithConfiguredCompany: it usually creates IQD with scale 0 or 3.
    // Let's rely on the math: 1000 units * 1450 rate = 1450000 units of company currency.
    // We need to be careful about minor units.
    // If USD has scale 2 (1000.00), Money::of(1000, 'USD') creates amount 1000.00
    // converted to IQD (let's say scale 0) : 1000 * 1450 = 1,450,000

    $asset = \Kezi\Accounting\Models\Asset::where('source_id', $vendorBill->id)->first();

    expect($asset)->not->toBeNull();
    // We assert loose equality or range because of floating point math potential
    // But since Money lib is used, it should be precise.

    // Asset purchase_value is stored as Money casts.
    // The value stored in DB is minor amount.
    // If company currency is IQD (scale 0), 1,450,000 is stored as 1450000.
    // If scale 3, then it depends. Typically IQD is used with 0 decimals in this system?

    // Let's check what value we got.
    // 1000 USD * 1450 = 1,450,000 IQD.

    $expectedAmount = 1450000;

    // Adjust if company currency scale is different (e.g. 3 decimals => * 1000)
    // The trait WithConfiguredCompany usually sets up IQD.
    // Let's assume standard behavior first.

    // IMPORTANT: The Asset model uses BaseCurrencyMoneyCast, which relies on Company Currency.
    // We need to check the raw value or the object.

    // Due to casting complexity in test environment, we verify the raw calculation logic
    // or expected outcome.

    // If the Asset purchase_value is NOT converted (bug), it will be 1000.
    // If it is converted, it will be 1,450,000.

    // Note: purchase_value cast might return Money object in Company Currency.
    // Let's get integer amount.
    expect($asset->purchase_value->getAmount()->toInt())->toBe((int) $expectedAmount);
    expect($asset->currency_id)->toBe($usd->id);
});

it('does not create asset for zero value line', function () {
    $this->setupWithConfiguredCompany();
    $this->vendor = Partner::factory()->for($this->company)->vendor()->create();
    $assetAccount = Account::factory()->for($this->company)->create(['type' => 'fixed_assets']);
    $accumDepAccount = Account::factory()->for($this->company)->create(['type' => 'non_current_assets']);
    $depExpenseAccount = Account::factory()->for($this->company)->create(['type' => 'depreciation']);

    $category = AssetCategory::create([
        'company_id' => $this->company->id,
        'name' => 'Zero Category',
        'asset_account_id' => $assetAccount->id,
        'accumulated_depreciation_account_id' => $accumDepAccount->id,
        'depreciation_expense_account_id' => $depExpenseAccount->id,
        'depreciation_method' => DepreciationMethod::StraightLine,
        'useful_life_years' => 5,
    ]);

    // So we add a dummy successful line to allow posting, so we can test the specific zero-asset behavior.
    $lines = [
        new CreateVendorBillLineDTO(
            product_id: null,
            description: 'Free Sample',
            quantity: 1,
            unit_price: Money::of(0, $this->company->currency->code),
            expense_account_id: $assetAccount->id,
            tax_id: null,
            analytic_account_id: null,
            asset_category_id: $category->id,
        ),
        new CreateVendorBillLineDTO(
            product_id: null,
            description: 'Real Item',
            quantity: 1,
            unit_price: Money::of(100, $this->company->currency->code),
            expense_account_id: $assetAccount->id,
            tax_id: null,
            analytic_account_id: null,
        ),
    ];

    $vendorBillDTO = new CreateVendorBillDTO(
        company_id: $this->company->id,
        vendor_id: $this->vendor->id,
        currency_id: $this->company->currency_id,
        bill_reference: 'ZERO-ASSET',
        bill_date: now()->toDateString(),
        accounting_date: now()->toDateString(),
        due_date: now()->addDays(30)->toDateString(),
        lines: $lines,
        created_by_user_id: $this->user->id,
    );

    $vendorBill = app(CreateVendorBillAction::class)->execute($vendorBillDTO);
    app(VendorBillService::class)->post($vendorBill, $this->user);

    // Should create 1 asset for the Real Item? No, Real Item is NOT linked to category.
    // Free Sample IS linked to category to asset.

    // We expect 0 assets from the Free Sample line.

    $assets = \Kezi\Accounting\Models\Asset::where('source_id', $vendorBill->id)->get();

    // Logic: if value is 0, Create Asset might still run but create a 0 value asset.
    // This looks like a valid behavior to check (maybe we allow 0 value assets? e.g. Fully Depreciated Gift).
    // But for now, let's assume we expect it NOT to create one if that's the "Edge case" we want to prevent.
    // However, the current code in Listener creates it unconditionally if category is present.

    // "Zero/Negative Values: Verify system behavior ... likely prevent asset creation"
    // So if it creates it, we fail per the requirement to PREVENT it.
    // Or we assert it creates it and we mark it as "System creates 0 value assets, which is acceptable/not acceptable".

    // Let's assert count is 0. If it fails, we fix the code to prevent it.
    expect($assets->count())->toBe(0);
});

it('deletes draft asset when vendor bill is cancelled', function () {
    $this->setupWithConfiguredCompany();
    $this->vendor = Partner::factory()->for($this->company)->vendor()->create();
    $assetAccount = Account::factory()->for($this->company)->create(['type' => 'fixed_assets']);
    $accumDepAccount = Account::factory()->for($this->company)->create(['type' => 'non_current_assets']);
    $depExpenseAccount = Account::factory()->for($this->company)->create(['type' => 'depreciation']);

    $category = AssetCategory::create([
        'company_id' => $this->company->id,
        'name' => 'Cancel Category',
        'asset_account_id' => $assetAccount->id,
        'accumulated_depreciation_account_id' => $accumDepAccount->id,
        'depreciation_expense_account_id' => $depExpenseAccount->id,
        'depreciation_method' => DepreciationMethod::StraightLine,
        'useful_life_years' => 5,
    ]);

    $vendorBillDTO = new CreateVendorBillDTO(
        company_id: $this->company->id,
        vendor_id: $this->vendor->id,
        currency_id: $this->company->currency_id,
        bill_reference: 'CANCEL-TEST',
        bill_date: now()->toDateString(),
        accounting_date: now()->toDateString(),
        due_date: now()->addDays(30)->toDateString(),
        lines: [
            new CreateVendorBillLineDTO(
                product_id: null,
                description: 'To Be Cancelled',
                quantity: 1,
                unit_price: Money::of(5000, $this->company->currency->code),
                expense_account_id: $assetAccount->id,
                tax_id: null,
                analytic_account_id: null,
                asset_category_id: $category->id,
            ),
        ],
        created_by_user_id: $this->user->id,
    );

    $vendorBill = app(CreateVendorBillAction::class)->execute($vendorBillDTO);
    app(VendorBillService::class)->post($vendorBill, $this->user);

    // Verify asset exists
    $this->assertDatabaseHas('assets', ['name' => 'To Be Cancelled', 'status' => 'draft']);

    // Cancel the bill
    app(VendorBillService::class)->cancel($vendorBill, $this->user, 'Testing cancellation');

    // Verify asset is gone or status changed?
    // Requirement: "Verify that cancelling ... correctly updates those assets (e.g., deletes them if they are still in 'draft')"

    $this->assertDatabaseMissing('assets', ['name' => 'To Be Cancelled']);
});
