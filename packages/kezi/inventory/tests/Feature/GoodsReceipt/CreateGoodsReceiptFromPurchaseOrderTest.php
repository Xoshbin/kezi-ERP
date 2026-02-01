<?php

use App\Models\Company;
use App\Models\User;
use Kezi\Accounting\Enums\Accounting\AccountType;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Journal;
use Kezi\Foundation\Enums\Partners\PartnerType;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Inventory\Actions\GoodsReceipt\CreateGoodsReceiptFromPurchaseOrderAction;
use Kezi\Inventory\DataTransferObjects\ReceiveGoodsFromPurchaseOrderDTO;
use Kezi\Inventory\DataTransferObjects\ValidateGoodsReceiptDTO;
use Kezi\Inventory\Enums\Inventory\StockLocationType;
use Kezi\Inventory\Enums\Inventory\StockPickingState;
use Kezi\Inventory\Enums\Inventory\StockPickingType;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Inventory\Models\StockPicking;
use Kezi\Inventory\Models\StockQuant;
use Kezi\Inventory\Services\Inventory\GoodsReceiptService;
use Kezi\Product\Enums\Products\ProductType;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Kezi\Purchase\Models\PurchaseOrder;
use Kezi\Purchase\Models\PurchaseOrderLine;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->company = Company::factory()->create();
    $this->currency = Currency::factory()->create(['code' => 'USD']);
    $this->company->update(['currency_id' => $this->currency->id]);

    // Set tenant context
    filament()->setTenant($this->company);

    // Create required accounts for inventory valuation
    $inventoryAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '1200',
        'name' => 'Inventory',
        'type' => AccountType::CurrentAssets,
    ]);

    $stockInputAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '2100',
        'name' => 'Stock Input',
        'type' => AccountType::CurrentLiabilities,
    ]);

    $payableAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '2000',
        'name' => 'Accounts Payable',
        'type' => AccountType::Payable,
    ]);

    // Create purchase journal
    $purchaseJournal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'purchase',
        'name' => 'Vendor Bills',
    ]);

    // Configure company with required defaults
    $this->company->update([
        'default_purchase_journal_id' => $purchaseJournal->id,
        'default_accounts_payable_id' => $payableAccount->id,
        'default_stock_input_account_id' => $stockInputAccount->id,
        'default_inventory_account_id' => $inventoryAccount->id,
    ]);

    $this->vendor = Partner::factory()->create([
        'company_id' => $this->company->id,
        'type' => PartnerType::Vendor,
    ]);

    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
    ]);

    // Create stock locations
    $this->vendorLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Vendors',
        'type' => StockLocationType::Vendor,
    ]);

    $this->warehouseLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Main Warehouse',
        'type' => StockLocationType::Internal,
    ]);

    $this->company->update(['default_stock_location_id' => $this->warehouseLocation->id]);
});

it('creates a GRN (StockPicking) from a confirmed purchase order', function () {
    // Arrange
    $po = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->currency->id,
        'status' => PurchaseOrderStatus::Confirmed,
        'created_by_user_id' => $this->user->id,
    ]);

    $poLine = PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $po->id,
        'product_id' => $this->product->id,
        'quantity' => 10.0,
    ]);

    // Act
    $dto = new ReceiveGoodsFromPurchaseOrderDTO(
        purchaseOrder: $po,
        userId: $this->user->id,
    );

    $action = app(CreateGoodsReceiptFromPurchaseOrderAction::class);
    $grn = $action->execute($dto);

    // Assert
    expect($grn)
        ->toBeInstanceOf(StockPicking::class)
        ->type->toBe(StockPickingType::Receipt)
        ->state->toBe(StockPickingState::Draft)
        ->purchase_order_id->toBe($po->id)
        ->partner_id->toBe($this->vendor->id);

    expect($grn->stockMoves)->toHaveCount(1);
    expect($grn->stockMoves->first()->productLines)->toHaveCount(1);
    expect((float) $grn->stockMoves->first()->productLines->first()->quantity)->toEqual(10.0);
});

it('validates a GRN and updates inventory', function () {
    // Arrange
    $po = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->currency->id,
        'status' => PurchaseOrderStatus::Confirmed,
        'created_by_user_id' => $this->user->id,
    ]);

    $poLine = PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $po->id,
        'product_id' => $this->product->id,
        'quantity' => 10.0,
        'quantity_received' => 0.0,
    ]);

    $service = app(GoodsReceiptService::class);

    // Create the GRN
    $grn = $service->createFromPurchaseOrder(new ReceiveGoodsFromPurchaseOrderDTO(
        purchaseOrder: $po,
        userId: $this->user->id,
    ));

    // Move to confirmed/assigned state
    $grn->update(['state' => StockPickingState::Assigned]);

    // Act - Validate the GRN
    $validatedGrn = $service->validate(new ValidateGoodsReceiptDTO(
        stockPicking: $grn,
        userId: $this->user->id,
        lines: [],
        createBackorder: false,
    ));

    // Assert
    expect($validatedGrn->state)->toBe(StockPickingState::Done);
    expect($validatedGrn->grn_number)->not->toBeNull();
    expect($validatedGrn->validated_at)->not->toBeNull();

    // Check inventory was updated
    $quant = StockQuant::where('company_id', $this->company->id)
        ->where('product_id', $this->product->id)
        ->where('location_id', $this->warehouseLocation->id)
        ->first();

    expect($quant)->not->toBeNull();
    expect((float) $quant->quantity)->toEqual(10.0);
});

it('creates a backorder for partial receipt', function () {
    // Arrange
    $po = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->currency->id,
        'status' => PurchaseOrderStatus::Confirmed,
        'created_by_user_id' => $this->user->id,
    ]);

    $poLine = PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $po->id,
        'product_id' => $this->product->id,
        'quantity' => 50.0,
        'quantity_received' => 0.0,
    ]);

    $service = app(GoodsReceiptService::class);

    // Create and confirm the GRN
    $grn = $service->createFromPurchaseOrder(new ReceiveGoodsFromPurchaseOrderDTO(
        purchaseOrder: $po,
        userId: $this->user->id,
    ));

    $grn->update(['state' => StockPickingState::Assigned]);

    // Act - Validate with partial quantity (30 of 50)
    $partialDto = new ValidateGoodsReceiptDTO(
        stockPicking: $grn,
        userId: $this->user->id,
        lines: [new \Kezi\Inventory\DataTransferObjects\ReceiveGoodsLineDTO(
            purchaseOrderLineId: $poLine->id,
            quantityToReceive: 30.0,
        )],
        createBackorder: true,
    );

    $validatedGrn = $service->validate($partialDto);

    // Assert - Original GRN is done
    expect($validatedGrn->state)->toBe(StockPickingState::Done);

    // Assert - Backorder was created for remaining 20 units
    $backorder = StockPicking::where('purchase_order_id', $po->id)
        ->where('origin', 'LIKE', '%Backorder%')
        ->first();

    expect($backorder)->not->toBeNull();
    expect($backorder->state)->toBe(StockPickingState::Assigned);

    // Check the backorder has correct quantity
    $backorderProductLine = $backorder->stockMoves->first()?->productLines->first();
    expect((float) $backorderProductLine->quantity)->toEqual(20.0);

    // Check inventory - only 30 units received
    $quant = StockQuant::where('company_id', $this->company->id)
        ->where('product_id', $this->product->id)
        ->where('location_id', $this->warehouseLocation->id)
        ->first();

    expect($quant->quantity)->toBe(30.0);
});

it('updates PurchaseOrder status when GRN is validated', function () {
    // Arrange
    $po = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->currency->id,
        'status' => PurchaseOrderStatus::Confirmed,
        'created_by_user_id' => $this->user->id,
    ]);

    $poLine = PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $po->id,
        'product_id' => $this->product->id,
        'quantity' => 10.0,
        'quantity_received' => 0.0,
    ]);

    $service = app(GoodsReceiptService::class);

    // Create and validate GRN
    $grn = $service->createFromPurchaseOrder(new ReceiveGoodsFromPurchaseOrderDTO(
        purchaseOrder: $po,
        userId: $this->user->id,
    ));

    $grn->update(['state' => StockPickingState::Assigned]);

    $service->validate(new ValidateGoodsReceiptDTO(
        stockPicking: $grn,
        userId: $this->user->id,
        lines: [],
        createBackorder: false,
    ));

    // Assert - PO moved to "To Bill" status after full receipt
    $po->refresh();
    expect($po->status)->toBe(PurchaseOrderStatus::ToBill);
});

it('prevents GRN creation for PO without storable products', function () {
    // Arrange - Create PO with service-type product
    $serviceProduct = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Service,
    ]);

    $po = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->currency->id,
        'status' => PurchaseOrderStatus::Confirmed,
        'created_by_user_id' => $this->user->id,
    ]);

    PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $po->id,
        'product_id' => $serviceProduct->id,
        'quantity' => 5.0,
    ]);

    // Act & Assert
    $action = app(CreateGoodsReceiptFromPurchaseOrderAction::class);

    expect(fn () => $action->execute(new ReceiveGoodsFromPurchaseOrderDTO(
        purchaseOrder: $po,
        userId: $this->user->id,
    )))->toThrow(\InvalidArgumentException::class, 'no storable products');
});

it('links StockPicking to PurchaseOrder via purchase_order_id', function () {
    // Arrange
    $po = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->currency->id,
        'status' => PurchaseOrderStatus::Confirmed,
        'created_by_user_id' => $this->user->id,
    ]);

    PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $po->id,
        'product_id' => $this->product->id,
        'quantity' => 10.0,
    ]);

    // Act
    $action = app(CreateGoodsReceiptFromPurchaseOrderAction::class);
    $grn = $action->execute(new ReceiveGoodsFromPurchaseOrderDTO(
        purchaseOrder: $po,
        userId: $this->user->id,
    ));

    // Assert - Bidirectional relationship
    expect($grn->purchase_order_id)->toBe($po->id);
    expect($grn->purchaseOrder->id)->toBe($po->id);
});
