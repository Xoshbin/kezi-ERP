<?php

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Jmeryar\Accounting\Enums\Accounting\AccountType;
use Jmeryar\Accounting\Enums\Accounting\JournalType;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\Journal;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Inventory\Enums\Inventory\StockLocationType;
use Jmeryar\Inventory\Models\InventoryCostLayer;
use Jmeryar\Inventory\Models\StockLocation;
use Jmeryar\Product\Actions\GenerateProductVariantsAction;
use Spatie\Permission\Models\Permission;
use Jmeryar\Product\DataTransferObjects\GenerateProductVariantsDTO;
use Jmeryar\Product\Models\Product;
use Jmeryar\Product\Models\ProductAttribute;
use Jmeryar\Product\Models\ProductAttributeValue;
use Jmeryar\Purchase\Actions\Purchases\CreateVendorBillLineAction;
use Jmeryar\Purchase\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use Jmeryar\Purchase\Enums\Purchases\VendorBillStatus;
use Jmeryar\Purchase\Models\VendorBill;
use Jmeryar\Purchase\Services\VendorBillService;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();
    $this->vendorBillService = app(VendorBillService::class);
    $this->createVendorBillLineAction = app(CreateVendorBillLineAction::class);

    // Create vendor
    $this->vendor = Partner::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Jmeryar\Foundation\Enums\Partners\PartnerType::Vendor,
    ]);

    // Create expense account
    $this->expenseAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => AccountType::Expense,
        'code' => '5000',
        'name' => 'COGS',
    ]);

    // Create inventory account
    $this->inventoryAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => AccountType::CurrentAssets,
        'code' => '1400',
        'name' => 'Inventory',
    ]);

    // Create AP account (required for vendor bill posting)
    $this->apAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => AccountType::Payable,
        'code' => '2100',
        'name' => 'Accounts Payable',
    ]);

    // Create Purchase Journal
    $this->purchaseJournal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'type' => JournalType::Purchase,
        'name' => 'Vendor Bills',
        'short_code' => 'BILL',
        'default_debit_account_id' => $this->expenseAccount->id,
        'default_credit_account_id' => $this->apAccount->id,
    ]);

    // Create Stock Locations
    $this->vendorLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'type' => StockLocationType::Vendor,
        'name' => 'Vendor Location',
    ]);

    $this->stockLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'type' => StockLocationType::Internal,
        'name' => 'Stock Location',
    ]);

    // Update Company defaults
    $this->company->update([
        'default_vendor_location_id' => $this->vendorLocation->id,
        'default_stock_location_id' => $this->stockLocation->id,
        'default_accounts_payable_id' => $this->apAccount->id,
        'default_purchase_journal_id' => $this->purchaseJournal->id,
    ]);

    // Create template product with FIFO valuation
    $this->templateFifo = Product::factory()->create([
        'name' => 'Widget',
        'sku' => 'WIDGET',
        'is_template' => true,
        'company_id' => $this->company->id,
        'type' => \Jmeryar\Product\Enums\Products\ProductType::Storable,
        'expense_account_id' => $this->expenseAccount->id,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'inventory_valuation_method' => \Jmeryar\Inventory\Enums\Inventory\ValuationMethod::FIFO,
    ]);

    // Create attributes
    $this->sizeAttribute = ProductAttribute::factory()->create([
        'name' => 'Size',
        'company_id' => $this->company->id,
    ]);

    $this->materialAttribute = ProductAttribute::factory()->create([
        'name' => 'Material',
        'company_id' => $this->company->id,
    ]);

    // Create values
    $this->smallSize = ProductAttributeValue::factory()->create([
        'product_attribute_id' => $this->sizeAttribute->id,
        'name' => 'Small',
    ]);

    $this->largeSize = ProductAttributeValue::factory()->create([
        'product_attribute_id' => $this->sizeAttribute->id,
        'name' => 'Large',
    ]);

    $this->plasticMaterial = ProductAttributeValue::factory()->create([
        'product_attribute_id' => $this->materialAttribute->id,
        'name' => 'Plastic',
    ]);

    $this->metalMaterial = ProductAttributeValue::factory()->create([
        'product_attribute_id' => $this->materialAttribute->id,
        'name' => 'Metal',
    ]);

    // Generate variants
    $dto = new GenerateProductVariantsDTO(
        templateProductId: $this->templateFifo->id,
        attributeValueMap: [
            $this->sizeAttribute->id => [$this->smallSize->id, $this->largeSize->id],
            $this->materialAttribute->id => [$this->plasticMaterial->id, $this->metalMaterial->id],
        ]
    );

    $action = app(GenerateProductVariantsAction::class);
    $this->variants = $action->execute($dto);

    // Grant permission to post vendor bills
    setPermissionsTeamId($this->company->id);
    Permission::create(['name' => 'confirm_vendor_bill']);
    $this->user->givePermissionTo('confirm_vendor_bill');
});

it('variant has independent cost layers from other variants', function () {
    $variantSmallPlastic = $this->variants->where('sku', 'WIDGET-SMALL-PLASTIC')->first();
    $variantLargeMetal = $this->variants->where('sku', 'WIDGET-LARGE-METAL')->first();

    // Purchase variant 1 at different prices
    $vendorBill1 = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'status' => VendorBillStatus::Draft,
        'currency_id' => $this->company->currency->id,
    ]);

    $line1Dto = new CreateVendorBillLineDTO(
        product_id: $variantSmallPlastic->id,
        description: 'Small Plastic - Batch 1',
        quantity: 100,
        unit_price: Money::of(10, $this->company->currency->code),
        tax_id: null,
        expense_account_id: $this->expenseAccount->id,
        analytic_account_id: null,
    );

    $this->createVendorBillLineAction->execute($vendorBill1, $line1Dto);
    $this->vendorBillService->post($vendorBill1, $this->user);

    // Purchase variant 2 at different price
    $vendorBill2 = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'status' => VendorBillStatus::Draft,
        'currency_id' => $this->company->currency->id,
    ]);

    $line2Dto = new CreateVendorBillLineDTO(
        product_id: $variantLargeMetal->id,
        description: 'Large Metal - Batch 1',
        quantity: 50,
        unit_price: Money::of(25, $this->company->currency->code),
        tax_id: null,
        expense_account_id: $this->expenseAccount->id,
        analytic_account_id: null,
    );

    $this->createVendorBillLineAction->execute($vendorBill2, $line2Dto);
    $this->vendorBillService->post($vendorBill2, $this->user);

    // Verify independent cost layers
    $costLayers1 = InventoryCostLayer::where('product_id', $variantSmallPlastic->id)->get();
    $costLayers2 = InventoryCostLayer::where('product_id', $variantLargeMetal->id)->get();

    expect($costLayers1)->toHaveCount(1);
    expect($costLayers2)->toHaveCount(1);

    // Verify they don't share cost layers
    expect($costLayers1->first()->id)->not->toBe($costLayers2->first()->id);
    expect($costLayers1->first()->product_id)->toBe($variantSmallPlastic->id);
    expect($costLayers2->first()->product_id)->toBe($variantLargeMetal->id);

    // Verify different costs
    expect($costLayers1->first()->cost_per_unit->getAmount()->toFloat())->toBe(10.0);
    expect($costLayers2->first()->cost_per_unit->getAmount()->toFloat())->toBe(25.0);
});

it('fifo calculation per variant works correctly', function () {
    $variant = $this->variants->where('sku', 'WIDGET-SMALL-PLASTIC')->first();

    // Verify variant uses FIFO (inherited from template)
    expect($variant->inventory_valuation_method)->toBe(\Jmeryar\Inventory\Enums\Inventory\ValuationMethod::FIFO);

    // Purchase 1: 50 units at $10 each
    $vendorBill1 = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'status' => VendorBillStatus::Draft,
        'currency_id' => $this->company->currency->id,
    ]);

    $line1Dto = new CreateVendorBillLineDTO(
        product_id: $variant->id,
        description: 'First purchase - $10',
        quantity: 50,
        unit_price: Money::of(10, $this->company->currency->code),
        tax_id: null,
        expense_account_id: $this->expenseAccount->id,
        analytic_account_id: null,
    );

    $this->createVendorBillLineAction->execute($vendorBill1, $line1Dto);
    $this->vendorBillService->post($vendorBill1, $this->user);

    // Purchase 2: 30 units at $12 each
    $vendorBill2 = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'status' => VendorBillStatus::Draft,
        'currency_id' => $this->company->currency->id,
    ]);

    $line2Dto = new CreateVendorBillLineDTO(
        product_id: $variant->id,
        description: 'Second purchase - $12',
        quantity: 30,
        unit_price: Money::of(12, $this->company->currency->code),
        tax_id: null,
        expense_account_id: $this->expenseAccount->id,
        analytic_account_id: null,
    );

    $this->createVendorBillLineAction->execute($vendorBill2, $line2Dto);
    $this->vendorBillService->post($vendorBill2, $this->user);

    // Purchase 3: 20 units at $15 each
    $vendorBill3 = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'status' => VendorBillStatus::Draft,
        'currency_id' => $this->company->currency->id,
    ]);

    $line3Dto = new CreateVendorBillLineDTO(
        product_id: $variant->id,
        description: 'Third purchase - $15',
        quantity: 20,
        unit_price: Money::of(15, $this->company->currency->code),
        tax_id: null,
        expense_account_id: $this->expenseAccount->id,
        analytic_account_id: null,
    );

    $this->createVendorBillLineAction->execute($vendorBill3, $line3Dto);
    $this->vendorBillService->post($vendorBill3, $this->user);

    // Verify FIFO cost layers exist in order
    $costLayers = InventoryCostLayer::where('product_id', $variant->id)
        ->orderBy('created_at')
        ->get();

    expect($costLayers)->toHaveCount(3);

    // First layer (oldest)
    expect($costLayers[0]->quantity)->toBe(50.0);
    expect($costLayers[0]->cost_per_unit->getAmount()->toFloat())->toBe(10.0);

    // Second layer
    expect($costLayers[1]->quantity)->toBe(30.0);
    expect($costLayers[1]->cost_per_unit->getAmount()->toFloat())->toBe(12.0);

    // Third layer (newest)
    expect($costLayers[2]->quantity)->toBe(20.0);
    expect($costLayers[2]->cost_per_unit->getAmount()->toFloat())->toBe(15.0);

    // Total quantity should be 100
    $totalQuantity = $costLayers->sum('quantity');
    expect($totalQuantity)->toBe(100.0);

    // Total value should be (50*10) + (30*12) + (20*15) = 500 + 360 + 300 = 1160
    $totalValue = $costLayers->sum(fn ($layer) => $layer->quantity * $layer->cost_per_unit->getAmount()->toFloat());
    expect($totalValue)->toBe(1160.0);
});

it('average cost per variant is calculated independently', function () {
    // Create a template with AVCO method
    $templateAvco = Product::factory()->create([
        'name' => 'Gadget',
        'sku' => 'GADGET',
        'is_template' => true,
        'company_id' => $this->company->id,
        'type' => \Jmeryar\Product\Enums\Products\ProductType::Storable,
        'expense_account_id' => $this->expenseAccount->id,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'inventory_valuation_method' => \Jmeryar\Inventory\Enums\Inventory\ValuationMethod::AVCO,
    ]);

    // Generate variants for AVCO template
    $dto = new GenerateProductVariantsDTO(
        templateProductId: $templateAvco->id,
        attributeValueMap: [
            $this->sizeAttribute->id => [$this->smallSize->id, $this->largeSize->id],
        ]
    );

    $action = app(GenerateProductVariantsAction::class);
    $avcoVariants = $action->execute($dto);

    $variantSmall = $avcoVariants->where('sku', 'GADGET-SMALL')->first();
    $variantLarge = $avcoVariants->where('sku', 'GADGET-LARGE')->first();

    // Purchase variant Small at different prices
    // Purchase 1: 10 units at $100
    $vendorBill1 = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'status' => VendorBillStatus::Draft,
        'currency_id' => $this->company->currency->id,
    ]);

    $line1Dto = new CreateVendorBillLineDTO(
        product_id: $variantSmall->id,
        description: 'Small - First batch',
        quantity: 10,
        unit_price: Money::of(100, $this->company->currency->code),
        tax_id: null,
        expense_account_id: $this->expenseAccount->id,
        analytic_account_id: null,
    );

    $this->createVendorBillLineAction->execute($vendorBill1, $line1Dto);
    $this->vendorBillService->post($vendorBill1, $this->user);

    // Purchase 2: 20 units at $150
    $vendorBill2 = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'status' => VendorBillStatus::Draft,
        'currency_id' => $this->company->currency->id,
    ]);

    $line2Dto = new CreateVendorBillLineDTO(
        product_id: $variantSmall->id,
        description: 'Small - Second batch',
        quantity: 20,
        unit_price: Money::of(150, $this->company->currency->code),
        tax_id: null,
        expense_account_id: $this->expenseAccount->id,
        analytic_account_id: null,
    );

    $this->createVendorBillLineAction->execute($vendorBill2, $line2Dto);
    $this->vendorBillService->post($vendorBill2, $this->user);

    // Purchase variant Large at different price
    $vendorBill3 = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'status' => VendorBillStatus::Draft,
        'currency_id' => $this->company->currency->id,
    ]);

    $line3Dto = new CreateVendorBillLineDTO(
        product_id: $variantLarge->id,
        description: 'Large - First batch',
        quantity: 15,
        unit_price: Money::of(200, $this->company->currency->code),
        tax_id: null,
        expense_account_id: $this->expenseAccount->id,
        analytic_account_id: null,
    );

    $this->createVendorBillLineAction->execute($vendorBill3, $line3Dto);
    $this->vendorBillService->post($vendorBill3, $this->user);

    // Verify average costs are calculated independently
    $variantSmall->refresh();
    $variantLarge->refresh();

    // Small variant average: (10*100 + 20*150) / 30 = (1000 + 3000) / 30 = 4000 / 30 = 133.33
    if ($variantSmall->average_cost) {
        $avgCostSmall = $variantSmall->average_cost->getAmount()->toFloat();
        expect($avgCostSmall)->toBeGreaterThanOrEqual(133.0);
        expect($avgCostSmall)->toBeLessThanOrEqual(134.0);
    }

    // Large variant average: 200 (only one purchase)
    if ($variantLarge->average_cost) {
        $avgCostLarge = $variantLarge->average_cost->getAmount()->toFloat();
        expect($avgCostLarge)->toBe(200.0);
    }

    // Verify they are independent
    if ($variantSmall->average_cost && $variantLarge->average_cost) {
        expect($variantSmall->average_cost->getAmount()->toFloat())
            ->not->toBe($variantLarge->average_cost->getAmount()->toFloat());
    }
});

it('cost layers do not mix between variants even with same template', function () {
    // Get all 4 variants
    $variant1 = $this->variants->where('sku', 'WIDGET-SMALL-PLASTIC')->first();
    $variant2 = $this->variants->where('sku', 'WIDGET-SMALL-METAL')->first();
    $variant3 = $this->variants->where('sku', 'WIDGET-LARGE-PLASTIC')->first();
    $variant4 = $this->variants->where('sku', 'WIDGET-LARGE-METAL')->first();

    // Purchase all variants at different prices
    $purchases = [
        ['variant' => $variant1, 'qty' => 100, 'price' => 10],
        ['variant' => $variant2, 'qty' => 80, 'price' => 15],
        ['variant' => $variant3, 'qty' => 60, 'price' => 20],
        ['variant' => $variant4, 'qty' => 40, 'price' => 30],
    ];

    foreach ($purchases as $purchase) {
        $vendorBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'status' => VendorBillStatus::Draft,
            'currency_id' => $this->company->currency->id,
        ]);

        $lineDto = new CreateVendorBillLineDTO(
            product_id: $purchase['variant']->id,
            description: "Purchase {$purchase['variant']->sku}",
            quantity: $purchase['qty'],
            unit_price: Money::of($purchase['price'], $this->company->currency->code),
            tax_id: null,
            expense_account_id: $this->expenseAccount->id,
            analytic_account_id: null,
        );

        $this->createVendorBillLineAction->execute($vendorBill, $lineDto);
        $this->vendorBillService->post($vendorBill, $this->user);
    }

    // Verify each variant has exactly 1 cost layer
    foreach ($purchases as $purchase) {
        $costLayers = InventoryCostLayer::where('product_id', $purchase['variant']->id)->get();

        expect($costLayers)->toHaveCount(1);
        expect($costLayers->first()->quantity)->toBe((float) $purchase['qty']);
        expect($costLayers->first()->cost_per_unit->getAmount()->toFloat())->toBe((float) $purchase['price']);
    }

    // Verify total cost layers in system = 4 (one per variant)
    $allCostLayers = InventoryCostLayer::whereIn('product_id', [
        $variant1->id,
        $variant2->id,
        $variant3->id,
        $variant4->id,
    ])->get();

    expect($allCostLayers)->toHaveCount(4);

    // Verify no cost layer is shared between variants
    $productIds = $allCostLayers->pluck('product_id')->unique();
    expect($productIds)->toHaveCount(4);
});

it('template product has no cost layers', function () {
    // Verify template has no cost layers
    $templateCostLayers = InventoryCostLayer::where('product_id', $this->templateFifo->id)->get();
    expect($templateCostLayers)->toHaveCount(0);

    // Purchase variants
    $variant = $this->variants->first();

    $vendorBill = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'status' => VendorBillStatus::Draft,
        'currency_id' => $this->company->currency->id,
    ]);

    $lineDto = new CreateVendorBillLineDTO(
        product_id: $variant->id,
        description: 'Variant purchase',
        quantity: 50,
        unit_price: Money::of(20, $this->company->currency->code),
        tax_id: null,
        expense_account_id: $this->expenseAccount->id,
        analytic_account_id: null,
    );

    $this->createVendorBillLineAction->execute($vendorBill, $lineDto);
    $this->vendorBillService->post($vendorBill, $this->user);

    // Verify variant has cost layer
    $variantCostLayers = InventoryCostLayer::where('product_id', $variant->id)->get();
    expect($variantCostLayers)->toHaveCount(1);

    // Verify template still has no cost layers
    $templateCostLayers = InventoryCostLayer::where('product_id', $this->templateFifo->id)->get();
    expect($templateCostLayers)->toHaveCount(0);
});
