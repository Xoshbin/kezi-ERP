<?php

use App\Models\Company;
use App\Models\User;
use Modules\Accounting\Enums\Accounting\AccountType;
use Modules\Accounting\Enums\Accounting\JournalType;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Modules\Inventory\DataTransferObjects\Inventory\CreateStockMoveDTO;
use Modules\Inventory\DataTransferObjects\Inventory\CreateStockMoveProductLineDTO;
use Modules\Inventory\Enums\Inventory\StockMoveStatus;
use Modules\Inventory\Enums\Inventory\StockMoveType;
use Modules\Inventory\Models\StockLocation;
use Modules\Inventory\Models\StockMove;
use Modules\Inventory\Models\StockQuant;
use Modules\Inventory\Services\Inventory\StockMoveService;
use Modules\Inventory\Services\Inventory\StockReservationService;
use Modules\Product\Actions\GenerateProductVariantsAction;
use Modules\Product\DataTransferObjects\GenerateProductVariantsDTO;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductAttribute;
use Modules\Product\Models\ProductAttributeValue;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();
    $this->stockMoveService = app(StockMoveService::class);
    $this->stockReservationService = app(StockReservationService::class);

    // Create locations
    $this->fromLocation = StockLocation::factory()->create(['company_id' => $this->company->id]);
    $this->toLocation = StockLocation::factory()->create(['company_id' => $this->company->id]);

    // Create Accounts and Journals required for valuation
    $this->inventoryAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => AccountType::CurrentAssets,
        'name' => 'Inventory',
        'code' => '1400',
    ]);

    $this->stockInputAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => AccountType::CurrentLiabilities,
        'name' => 'Stock Input',
        'code' => '2100',
    ]);

    $this->cogsAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => AccountType::Expense,
        'name' => 'COGS',
        'code' => '5000',
    ]);

    $this->purchaseJournal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'type' => JournalType::Purchase,
        'name' => 'Vendor Bills',
    ]);

    $this->salesJournal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'type' => JournalType::Sale,
        'name' => 'Customer Invoices',
    ]);

    $this->company->update([
        'default_purchase_journal_id' => $this->purchaseJournal->id,
        'default_sales_journal_id' => $this->salesJournal->id,
    ]);

    // Create template product
    $this->template = Product::factory()->create([
        'name' => 'T-Shirt',
        'sku' => 'TSHIRT',
        'is_template' => true,
        'company_id' => $this->company->id,
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
    ]);

    // Create attributes and values
    $this->sizeAttribute = ProductAttribute::factory()->create([
        'name' => 'Size',
        'company_id' => $this->company->id,
    ]);

    $this->colorAttribute = ProductAttribute::factory()->create([
        'name' => 'Color',
        'company_id' => $this->company->id,
    ]);

    $this->smallValue = ProductAttributeValue::factory()->create([
        'product_attribute_id' => $this->sizeAttribute->id,
        'name' => 'Small',
    ]);

    $this->largeValue = ProductAttributeValue::factory()->create([
        'product_attribute_id' => $this->sizeAttribute->id,
        'name' => 'Large',
    ]);

    $this->redValue = ProductAttributeValue::factory()->create([
        'product_attribute_id' => $this->colorAttribute->id,
        'name' => 'Red',
    ]);

    $this->blueValue = ProductAttributeValue::factory()->create([
        'product_attribute_id' => $this->colorAttribute->id,
        'name' => 'Blue',
    ]);

    // Generate variants
    $dto = new GenerateProductVariantsDTO(
        templateProductId: $this->template->id,
        attributeValueMap: [
            $this->sizeAttribute->id => [$this->smallValue->id, $this->largeValue->id],
            $this->colorAttribute->id => [$this->redValue->id, $this->blueValue->id],
        ]
    );

    $action = app(GenerateProductVariantsAction::class);
    $this->variants = $action->execute($dto);
});

it('can create stock move for variant product', function () {
    // Get a variant (e.g., Small-Red)
    $variant = $this->variants->first();

    expect($variant)->not->toBeNull();
    expect($variant->isVariant())->toBeTrue();
    expect($variant->parent_product_id)->toBe($this->template->id);

    // Create stock move for variant
    $productLineDto = new CreateStockMoveProductLineDTO(
        product_id: $variant->id,
        quantity: 100.0,
        from_location_id: $this->fromLocation->id,
        to_location_id: $this->toLocation->id,
        description: 'Incoming stock for variant',
        source_type: null,
        source_id: null
    );

    $dto = new CreateStockMoveDTO(
        company_id: $this->company->id,
        product_lines: [$productLineDto],
        move_type: StockMoveType::Incoming,
        status: StockMoveStatus::Done,
        move_date: now(),
        reference: 'SM-VARIANT-001',
        description: 'Test variant stock move',
        source_type: null,
        source_id: null,
        created_by_user_id: $this->user->id
    );

    $stockMove = $this->stockMoveService->createMove($dto);

    expect($stockMove)->toBeInstanceOf(StockMove::class);
    expect($stockMove->status)->toBe(StockMoveStatus::Done);
    expect($stockMove->productLines)->toHaveCount(1);
    expect($stockMove->productLines->first()->product_id)->toBe($variant->id);

    // Verify stock quant was created for the variant
    $stockQuant = StockQuant::where('product_id', $variant->id)
        ->where('location_id', $this->toLocation->id)
        ->first();

    expect($stockQuant)->not->toBeNull();
    expect((float) $stockQuant->quantity)->toBe(100.0);
});

it('variant has independent stock levels from other variants', function () {
    // Get two different variants
    $variantSmallRed = $this->variants->where('sku', 'TSHIRT-SMALL-RED')->first();
    $variantLargeBlue = $this->variants->where('sku', 'TSHIRT-LARGE-BLUE')->first();

    expect($variantSmallRed)->not->toBeNull();
    expect($variantLargeBlue)->not->toBeNull();

    // Create stock move for first variant
    $productLineDto1 = new CreateStockMoveProductLineDTO(
        product_id: $variantSmallRed->id,
        quantity: 50.0,
        from_location_id: $this->fromLocation->id,
        to_location_id: $this->toLocation->id,
        description: 'Stock for Small-Red',
        source_type: null,
        source_id: null
    );

    $dto1 = new CreateStockMoveDTO(
        company_id: $this->company->id,
        product_lines: [$productLineDto1],
        move_type: StockMoveType::Incoming,
        status: StockMoveStatus::Done,
        move_date: now(),
        reference: 'SM-VAR-001',
        description: 'Stock for variant 1',
        source_type: null,
        source_id: null,
        created_by_user_id: $this->user->id
    );

    $this->stockMoveService->createMove($dto1);

    // Create stock move for second variant with different quantity
    $productLineDto2 = new CreateStockMoveProductLineDTO(
        product_id: $variantLargeBlue->id,
        quantity: 75.0,
        from_location_id: $this->fromLocation->id,
        to_location_id: $this->toLocation->id,
        description: 'Stock for Large-Blue',
        source_type: null,
        source_id: null
    );

    $dto2 = new CreateStockMoveDTO(
        company_id: $this->company->id,
        product_lines: [$productLineDto2],
        move_type: StockMoveType::Incoming,
        status: StockMoveStatus::Done,
        move_date: now(),
        reference: 'SM-VAR-002',
        description: 'Stock for variant 2',
        source_type: null,
        source_id: null,
        created_by_user_id: $this->user->id
    );

    $this->stockMoveService->createMove($dto2);

    // Verify independent stock levels
    $stockQuant1 = StockQuant::where('product_id', $variantSmallRed->id)
        ->where('location_id', $this->toLocation->id)
        ->first();

    $stockQuant2 = StockQuant::where('product_id', $variantLargeBlue->id)
        ->where('location_id', $this->toLocation->id)
        ->first();

    expect($stockQuant1)->not->toBeNull();
    expect($stockQuant2)->not->toBeNull();
    expect((float) $stockQuant1->quantity)->toBe(50.0);
    expect((float) $stockQuant2->quantity)->toBe(75.0);

    // Verify they are truly independent
    expect($stockQuant1->id)->not->toBe($stockQuant2->id);
    expect($stockQuant1->product_id)->not->toBe($stockQuant2->product_id);
});

it('template cannot have stock moves', function () {
    // Attempt to create stock move for template product
    $productLineDto = new CreateStockMoveProductLineDTO(
        product_id: $this->template->id,
        quantity: 100.0,
        from_location_id: $this->fromLocation->id,
        to_location_id: $this->toLocation->id,
        description: 'Attempting stock move for template',
        source_type: null,
        source_id: null
    );

    $dto = new CreateStockMoveDTO(
        company_id: $this->company->id,
        product_lines: [$productLineDto],
        move_type: StockMoveType::Incoming,
        status: StockMoveStatus::Done,
        move_date: now(),
        reference: 'SM-TEMPLATE-001',
        description: 'Invalid template stock move',
        source_type: null,
        source_id: null,
        created_by_user_id: $this->user->id
    );

    // This should throw an exception
    expect(fn () => $this->stockMoveService->createMove($dto))
        ->toThrow(\InvalidArgumentException::class, 'Cannot create stock moves for template products');
});

it('stock quant tracks variant separately', function () {
    // Create stock moves for all 4 variants
    $quantities = [
        'TSHIRT-SMALL-RED' => 10.0,
        'TSHIRT-SMALL-BLUE' => 20.0,
        'TSHIRT-LARGE-RED' => 30.0,
        'TSHIRT-LARGE-BLUE' => 40.0,
    ];

    foreach ($quantities as $sku => $quantity) {
        $variant = $this->variants->where('sku', $sku)->first();

        $productLineDto = new CreateStockMoveProductLineDTO(
            product_id: $variant->id,
            quantity: $quantity,
            from_location_id: $this->fromLocation->id,
            to_location_id: $this->toLocation->id,
            description: "Stock for {$sku}",
            source_type: null,
            source_id: null
        );

        $dto = new CreateStockMoveDTO(
            company_id: $this->company->id,
            product_lines: [$productLineDto],
            move_type: StockMoveType::Incoming,
            status: StockMoveStatus::Done,
            move_date: now(),
            reference: "SM-{$sku}",
            description: "Stock move for {$sku}",
            source_type: null,
            source_id: null,
            created_by_user_id: $this->user->id
        );

        $this->stockMoveService->createMove($dto);
    }

    // Verify each variant has its own stock quant
    foreach ($quantities as $sku => $expectedQuantity) {
        $variant = $this->variants->where('sku', $sku)->first();

        $stockQuant = StockQuant::where('product_id', $variant->id)
            ->where('location_id', $this->toLocation->id)
            ->first();

        expect($stockQuant)->not->toBeNull();
        expect((float) $stockQuant->quantity)->toBe($expectedQuantity);
    }

    // Verify template has no stock quants
    $templateStockQuants = StockQuant::where('product_id', $this->template->id)->count();
    expect($templateStockQuants)->toBe(0);
});

it('lot tracking works per variant', function () {
    // Enable lot tracking for template
    $this->template->update(['tracking_type' => \Modules\Inventory\Enums\Inventory\TrackingType::Lot]);

    $variant = $this->variants->first();
    // Simulate inheritance (since sync logic is deferred to Phase 2)
    $variant->update(['tracking_type' => \Modules\Inventory\Enums\Inventory\TrackingType::Lot]);

    // Verify variant inherits lot tracking
    expect($variant->tracking_type)->toBe(\Modules\Inventory\Enums\Inventory\TrackingType::Lot);

    // Create a lot for the variant
    $lot = \Modules\Inventory\Models\Lot::create([
        'company_id' => $this->company->id,
        'product_id' => $variant->id,
        'lot_code' => 'LOT-001',
    ]);

    // Create a mock Purchase Order Line as source to satisfy cost validation
    // Create purchase journal first (required for PO)
    $journal = \Modules\Accounting\Models\Journal::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Accounting\Enums\Accounting\JournalType::Purchase,
    ]);

    // Set default journal on company
    $this->company->update(['default_purchase_journal_id' => $journal->id]);

    $po = \Modules\Purchase\Models\PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'status' => \Modules\Purchase\Enums\Purchases\PurchaseOrderStatus::Confirmed,
        'currency_id' => $this->company->currency_id,
    ]);
    $poLine = \Modules\Purchase\Models\PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $po->id,
        'company_id' => $this->company->id,
        'product_id' => $variant->id,
        'quantity' => 100.0,
        'unit_price' => \Brick\Money\Money::of(10, $this->company->currency->code),
    ]);

    // Create stock move with lot
    $productLineDto = new \Modules\Inventory\DataTransferObjects\Inventory\CreateStockMoveProductLineDTO(
        product_id: $variant->id,
        quantity: 100.0,
        from_location_id: $this->fromLocation->id,
        to_location_id: $this->toLocation->id,
        description: 'Lot tracking test',
        source_type: null,
        source_id: null
    );

    // Let's create as draft first
    $dto = new \Modules\Inventory\DataTransferObjects\Inventory\CreateStockMoveDTO(
        company_id: $this->company->id,
        product_lines: [$productLineDto],
        move_type: \Modules\Inventory\Enums\Inventory\StockMoveType::Incoming,
        status: \Modules\Inventory\Enums\Inventory\StockMoveStatus::Draft,
        move_date: now(),
        reference: 'SM-LOT-001',
        description: 'Lot tracking test move',
        source_type: \Modules\Purchase\Models\PurchaseOrderLine::class,
        source_id: $poLine->id,
        created_by_user_id: $this->user->id
    );

    $stockMove = $this->stockMoveService->createMove($dto);

    // Assign lot by creating a StockMoveLine linked to the StockMoveProductLine
    $line = $stockMove->productLines->first();
    expect($line->stockMoveLines->count())->toBe(0); // Ensure no duplicate lines

    \Modules\Inventory\Models\StockMoveLine::create([
        'company_id' => $this->company->id,
        'stock_move_product_line_id' => $line->id,
        'lot_id' => $lot->id,
        'quantity' => 100.0,
    ]);

    $line->refresh();
    expect($line->stockMoveLines->count())->toBe(1); // Ensure only 1 line

    // Confirm the move
    $confirmDto = new \Modules\Inventory\DataTransferObjects\Inventory\ConfirmStockMoveDTO(
        stock_move_id: $stockMove->id,
    );
    $this->stockMoveService->confirmMove($confirmDto);

    // Verify stock quant has lot
    $stockQuant = \Modules\Inventory\Models\StockQuant::where('product_id', $variant->id)
        ->where('location_id', $this->toLocation->id)
        ->where('lot_id', $lot->id)
        ->first();

    expect($stockQuant)->not->toBeNull();
    expect((float) $stockQuant->quantity)->toBe(100.0);
});

it('stock reservations work per variant', function () {
    $variant = $this->variants->first();

    // Create initial stock
    $productLineDto = new CreateStockMoveProductLineDTO(
        product_id: $variant->id,
        quantity: 100.0,
        from_location_id: $this->fromLocation->id,
        to_location_id: $this->toLocation->id,
        description: 'Initial stock',
        source_type: null,
        source_id: null
    );

    $dto = new CreateStockMoveDTO(
        company_id: $this->company->id,
        product_lines: [$productLineDto],
        move_type: StockMoveType::Incoming,
        status: StockMoveStatus::Done,
        move_date: now(),
        reference: 'SM-INIT-001',
        description: 'Initial stock',
        source_type: null,
        source_id: null,
        created_by_user_id: $this->user->id
    );

    $this->stockMoveService->createMove($dto);

    // Create a draft outgoing stock move for reservation testing
    $reservationProductLineDto = new CreateStockMoveProductLineDTO(
        product_id: $variant->id,
        quantity: 30.0,
        from_location_id: $this->toLocation->id,
        to_location_id: $this->toLocation->id, // Same location for simplicity
        description: 'Reservation test',
        source_type: 'TestReservation',
        source_id: 1
    );

    $reservationDto = new CreateStockMoveDTO(
        company_id: $this->company->id,
        product_lines: [$reservationProductLineDto],
        move_type: StockMoveType::Outgoing,
        status: StockMoveStatus::Draft,
        move_date: now(),
        reference: 'SM-RESERVE-001',
        description: 'Reservation test move',
        source_type: 'TestReservation',
        source_id: 1,
        created_by_user_id: $this->user->id
    );

    $reservationMove = $this->stockMoveService->createMove($reservationDto);

    // Reserve stock for this move
    $reservedQuantity = $this->stockReservationService->reserveForMove(
        $reservationMove,
        $this->toLocation->id
    );

    expect($reservedQuantity)->toBe(30.0);

    // Verify stock quant shows reservation
    $stockQuant = StockQuant::where('product_id', $variant->id)
        ->where('location_id', $this->toLocation->id)
        ->first();

    expect($stockQuant)->not->toBeNull();
    expect((float) $stockQuant->quantity)->toBe(100.0);
    expect((float) $stockQuant->reserved_quantity)->toBe(30.0);

    // Verify available quantity
    $availableQuantity = $stockQuant->quantity - $stockQuant->reserved_quantity;
    expect((float) $availableQuantity)->toBe(70.0);
});

it('multiple variants can have different stock levels at different locations', function () {
    // Create a third location
    $location3 = StockLocation::factory()->create(['company_id' => $this->company->id]);

    $variantSmallRed = $this->variants->where('sku', 'TSHIRT-SMALL-RED')->first();
    $variantLargeBlue = $this->variants->where('sku', 'TSHIRT-LARGE-BLUE')->first();

    // Add stock for Small-Red at location 2
    $productLineDto1 = new CreateStockMoveProductLineDTO(
        product_id: $variantSmallRed->id,
        quantity: 50.0,
        from_location_id: $this->fromLocation->id,
        to_location_id: $this->toLocation->id,
        description: 'Small-Red at location 2',
        source_type: null,
        source_id: null
    );

    $dto1 = new CreateStockMoveDTO(
        company_id: $this->company->id,
        product_lines: [$productLineDto1],
        move_type: StockMoveType::Incoming,
        status: StockMoveStatus::Done,
        move_date: now(),
        reference: 'SM-LOC2-001',
        description: 'Stock at location 2',
        source_type: null,
        source_id: null,
        created_by_user_id: $this->user->id
    );

    $this->stockMoveService->createMove($dto1);

    // Add stock for Small-Red at location 3
    $productLineDto2 = new CreateStockMoveProductLineDTO(
        product_id: $variantSmallRed->id,
        quantity: 75.0,
        from_location_id: $this->fromLocation->id,
        to_location_id: $location3->id,
        description: 'Small-Red at location 3',
        source_type: null,
        source_id: null
    );

    $dto2 = new CreateStockMoveDTO(
        company_id: $this->company->id,
        product_lines: [$productLineDto2],
        move_type: StockMoveType::Incoming,
        status: StockMoveStatus::Done,
        move_date: now(),
        reference: 'SM-LOC3-001',
        description: 'Stock at location 3',
        source_type: null,
        source_id: null,
        created_by_user_id: $this->user->id
    );

    $this->stockMoveService->createMove($dto2);

    // Add stock for Large-Blue at location 2
    $productLineDto3 = new CreateStockMoveProductLineDTO(
        product_id: $variantLargeBlue->id,
        quantity: 100.0,
        from_location_id: $this->fromLocation->id,
        to_location_id: $this->toLocation->id,
        description: 'Large-Blue at location 2',
        source_type: null,
        source_id: null
    );

    $dto3 = new CreateStockMoveDTO(
        company_id: $this->company->id,
        product_lines: [$productLineDto3],
        move_type: StockMoveType::Incoming,
        status: StockMoveStatus::Done,
        move_date: now(),
        reference: 'SM-LOC2-002',
        description: 'Stock at location 2',
        source_type: null,
        source_id: null,
        created_by_user_id: $this->user->id
    );

    $this->stockMoveService->createMove($dto3);

    // Verify Small-Red has stock at two locations
    $smallRedLoc2 = StockQuant::where('product_id', $variantSmallRed->id)
        ->where('location_id', $this->toLocation->id)
        ->first();

    $smallRedLoc3 = StockQuant::where('product_id', $variantSmallRed->id)
        ->where('location_id', $location3->id)
        ->first();

    expect($smallRedLoc2)->not->toBeNull();
    expect($smallRedLoc3)->not->toBeNull();
    expect((float) $smallRedLoc2->quantity)->toBe(50.0);
    expect((float) $smallRedLoc3->quantity)->toBe(75.0);

    // Verify Large-Blue has stock only at location 2
    $largeBlueLoc2 = StockQuant::where('product_id', $variantLargeBlue->id)
        ->where('location_id', $this->toLocation->id)
        ->first();

    $largeBlueLoc3 = StockQuant::where('product_id', $variantLargeBlue->id)
        ->where('location_id', $location3->id)
        ->first();

    expect($largeBlueLoc2)->not->toBeNull();
    expect($largeBlueLoc3)->toBeNull();
    expect((float) $largeBlueLoc2->quantity)->toBe(100.0);

    // Verify total quantities per variant
    $totalSmallRed = StockQuant::where('product_id', $variantSmallRed->id)->sum('quantity');
    $totalLargeBlue = StockQuant::where('product_id', $variantLargeBlue->id)->sum('quantity');

    expect((float) $totalSmallRed)->toBe(125.0); // 50 + 75
    expect((float) $totalLargeBlue)->toBe(100.0);
});
