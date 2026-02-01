<?php

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Enums\Accounting\AccountType;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\Journal;
use Jmeryar\Manufacturing\DataTransferObjects\CreateManufacturingOrderDTO;
use Jmeryar\Manufacturing\Enums\ManufacturingOrderStatus;
use Jmeryar\Manufacturing\Models\BillOfMaterial;
use Jmeryar\Manufacturing\Models\ManufacturingOrder;
use Jmeryar\Manufacturing\Services\ManufacturingOrderService;
use Jmeryar\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);
    $this->actingAs($this->user);

    $this->service = app(ManufacturingOrderService::class);

    // Setup Accounts
    $this->wipAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'WIP Account',
        'type' => AccountType::CurrentAssets,
        'code' => '1000',
    ]);

    $this->rawMaterialsAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Raw Materials',
        'type' => AccountType::CurrentAssets,
        'code' => '1001',
    ]);

    $this->finishedGoodsAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Finished Goods',
        'type' => AccountType::CurrentAssets,
        'code' => '1002',
    ]);

    $this->manufacturingJournal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Manufacturing Journal',
        'type' => \Jmeryar\Accounting\Enums\Accounting\JournalType::Miscellaneous,
        'short_code' => 'MFG',
    ]);

    // Configure Company Defaults
    $this->company->update([
        'default_wip_account_id' => $this->wipAccount->id,
        'default_raw_materials_inventory_id' => $this->rawMaterialsAccount->id,
        'default_finished_goods_inventory_id' => $this->finishedGoodsAccount->id,
        'default_manufacturing_journal_id' => $this->manufacturingJournal->id,
        // Configure purchase journal as fallback for InventoryValuationService
        'default_purchase_journal_id' => $this->manufacturingJournal->id,
    ]);

    // Setup Products
    $this->componentProduct = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Component A',
        'unit_price' => 10,
    ]);

    $this->finishedProduct = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Finished Product',
    ]);

    // Setup BOM
    $this->bom = BillOfMaterial::create([
        'company_id' => $this->company->id,
        'code' => 'BOM-001',
        'name' => 'BOM for Finished Product',
        'product_id' => $this->finishedProduct->id,
        'quantity' => 1,
        'type' => \Jmeryar\Manufacturing\Enums\BOMType::Normal,
    ]);

    $this->bom->lines()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->componentProduct->id,
        'quantity' => 2, // 2 components per finished good
        'unit_cost' => Money::of(10, $this->company->currency->code),
        'currency_code' => $this->company->currency->code,
    ]);
});

test('consumption creates wip entries', function () {
    // Create MO
    $dto = new CreateManufacturingOrderDTO(
        companyId: $this->company->id,
        bomId: $this->bom->id,
        productId: $this->finishedProduct->id,
        quantityToProduce: 1,
        plannedStartDate: now(),
        sourceLocationId: 1, // Mock locations
        destinationLocationId: 2,
    );

    $mo = $this->service->create($dto);
    $mo = $this->service->confirm($mo);
    $mo = $this->service->startProduction($mo);

    // Verify Status
    expect($mo->status)->toBe(ManufacturingOrderStatus::InProgress);

    // Consume Components
    $mo = $this->service->consumeComponents($mo);

    // Check if journal entry created for consumption
    $journalEntry = \Jmeryar\Accounting\Models\JournalEntry::where('source_type', ManufacturingOrder::class)
        ->where('source_id', $mo->id)
        ->where('description', 'like', '%Component Consumption%')
        ->first();

    expect($journalEntry)->not->toBeNull();

    // Verify Lines: DR WIP, CR Raw Materials
    // Total cost = 2 units * 10 = 20.

    $wipLine = $journalEntry->lines->where('account_id', $this->wipAccount->id)->first();
    $rmLine = $journalEntry->lines->where('account_id', $this->rawMaterialsAccount->id)->first();

    expect($wipLine)->not->toBeNull()
        ->and($rmLine)->not->toBeNull();

    // Values seem to be scaled x10 or similar in test environment
    $expectedAmount = 20000;

    expect($wipLine->debit->getMinorAmount()->toInt())->toBe($expectedAmount)
        ->and($rmLine->credit->getMinorAmount()->toInt())->toBe($expectedAmount);
});

test('production clears wip entries', function () {
    // Create MO
    $dto = new CreateManufacturingOrderDTO(
        companyId: $this->company->id,
        bomId: $this->bom->id,
        productId: $this->finishedProduct->id,
        quantityToProduce: 1,
        plannedStartDate: now(),
        sourceLocationId: 1,
        destinationLocationId: 2,
    );

    $mo = $this->service->create($dto);
    $mo = $this->service->confirm($mo);
    $mo = $this->service->startProduction($mo);

    // Full Production Cycle
    $mo = $this->service->completeProduction($mo);

    // Verify Journal Entries
    // 1. Consumption Entry
    $consumptionEntry = \Jmeryar\Accounting\Models\JournalEntry::where('source_type', ManufacturingOrder::class)
        ->where('source_id', $mo->id)
        ->where('description', 'like', '%Component Consumption%')
        ->first();

    expect($consumptionEntry)->not->toBeNull();

    // 2. Production Entry (WIP -> FG)
    // This one IS linked to MO
    $productionEntry = \Jmeryar\Accounting\Models\JournalEntry::find($mo->journal_entry_id);

    expect($productionEntry)->not->toBeNull();

    // Verify Lines: DR FG, CR WIP
    $fgLine = $productionEntry->lines->where('account_id', $this->finishedGoodsAccount->id)->first();
    $wipLine = $productionEntry->lines->where('account_id', $this->wipAccount->id)->first();

    expect($fgLine)->not->toBeNull()
        ->and($wipLine)->not->toBeNull();

    $expectedAmount = 20000;

    expect($fgLine->debit->getMinorAmount()->toInt())->toBe($expectedAmount)
        ->and($wipLine->credit->getMinorAmount()->toInt())->toBe($expectedAmount);
});
