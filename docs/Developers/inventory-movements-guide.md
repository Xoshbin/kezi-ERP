---
title: Understanding Inventory Movements
icon: heroicon-o-cube-transparent
order: 5
---

# Understanding Inventory Movements in JMeryar ERP

This developer guide explains how the inventory system tracks items coming in and going out. Think of it like understanding how a warehouse really works, but with all the accounting tied in.

---

## The Big Picture

The inventory system answers four key questions:

1. **How much stuff you have** → Stock Quantities (`StockQuant`)
2. **Where that stuff is** → Locations (`StockLocation`)
3. **How stuff moves around** → Stock Movements (`StockMove`)
4. **What that stuff cost you** → Valuation (`InventoryCostLayer`, `Product.average_cost`)

---

## Key Building Blocks (The Models)

### 1. StockQuant - "The Counter"

**Path**: `Modules/Inventory/app/Models/StockQuant.php`

This is your "current inventory snapshot." It tracks the quantity of a specific product at a specific location:

| Product     | Location     | Quantity | Reserved | Available |
|-------------|--------------|----------|----------|-----------|
| iPhone 15   | Warehouse A  | 100      | 10       | 90        |
| iPhone 15   | Store B      | 25       | 0        | 25        |
| MacBook Pro | Warehouse A  | 50       | 5        | 45        |

**Key Fields**:
- `product_id` - The product being tracked
- `location_id` - Where the product is stored
- `quantity` - Total physical quantity on hand
- `reserved_quantity` - Quantity reserved for pending orders
- `lot_id` - Optional lot tracking for traceability

**Key Computed Value**:
```
Available = quantity - reserved_quantity
```

Reserved means someone placed an order, but we haven't shipped it yet.

---

### 2. StockMove - "The Transaction Record"

**Path**: `Modules/Inventory/app/Models/StockMove.php`

Every time inventory moves, there's a `StockMove` record. It's like a receipt that says:
> "On this date, we moved X units from Location A to Location B"

**Movement Types** (defined in `StockMoveType` enum):

| Type | Symbol | Description |
|------|--------|-------------|
| Incoming | 📥 | Stuff coming in (from vendors) |
| Outgoing | 📤 | Stuff going out (to customers) |
| Internal Transfer | 🔄 | Moving between your own warehouses |
| Adjustment | ⚖️ | Corrections (found extra items, or items damaged) |

**Status Workflow** (defined in `StockMoveStatus` enum):
```
Draft → Confirmed → Done
```

**Key Relationships**:
- `productLines` - The products and quantities being moved (`StockMoveProductLine`)
- `source` - Polymorphic link to the source document (VendorBill, Invoice, etc.)
- `stockMoveValuations` - The accounting valuation records

---

### 3. StockLocation - "The Places"

**Path**: `Modules/Inventory/app/Models/StockLocation.php`

Locations tell us WHERE things are. There are different types (defined in `StockLocationType` enum):

| Type | Description | Example |
|------|-------------|---------|
| Internal | Your warehouses | Main Warehouse, Store A |
| Vendor | Where you buy from | Supplier Holding Area |
| Customer | Where you sell to | Customer Delivery Point |
| Inventory Adjustment | A "virtual" location for corrections | Inventory Adjustments |

Locations can be hierarchical (parent-child relationships) for organizing complex warehouse structures.

---

### 4. InventoryCostLayer - "The Cost Tracker"

**Path**: `Modules/Inventory/app/Models/InventoryCostLayer.php`

For FIFO/LIFO valuation methods, this tracks each "layer" of cost:

```
Layer 1: Bought 100 units @ $10 each on Jan 1   (remaining: 0)
Layer 2: Bought 50 units @ $12 each on Feb 1    (remaining: 30)
Layer 3: Bought 75 units @ $11 each on Mar 1    (remaining: 75)
```

**Key Fields**:
- `product_id` - The product this layer belongs to
- `quantity` - Original quantity purchased
- `remaining_quantity` - Unconsumed quantity (decreases as items are sold)
- `cost_per_unit` - Cost per unit (stored as `Brick\Money\Money`)
- `purchase_date` - When this layer was created
- `source_type` / `source_id` - Link to the source document (VendorBill, etc.)

---

## How Movements Actually Work

### Incoming Stock (Purchases)

**Trigger**: When you post a Vendor Bill for storable products

**Action Path**: `ProcessIncomingStockAction.php`

```
                      ProcessIncomingStockAction
                              │
    ┌─────────────────────────┴─────────────────────────┐
    ▼                                                   ▼
StockQuantService.adjust()                 InventoryValuationService
(Increase quantity at                      (Update cost layers/avg cost
 destination location)                      + Create journal entries)
```

**What happens step-by-step**:

1. **Create Stock Move** → Records the movement with `move_type = Incoming`
2. **Update StockQuant** → Increases quantity at warehouse location
3. **Update Product Cost**:
   - **AVCO**: Recalculates weighted average cost
   - **FIFO/LIFO**: Creates a new `InventoryCostLayer`
4. **Create Journal Entry**:
   - Debit: Inventory Asset (asset increases)
   - Credit: Stock Input Liability (obligation recorded)

**Code Flow**:
```php
// ProcessIncomingStockAction::execute()
foreach ($stockMove->productLines as $productLine) {
    // 1. Extract cost from source document (VendorBill)
    $costPerUnit = $this->extractCostFromSource($stockMove, $productLine);
    
    // 2. Process valuation (creates cost layers or updates average cost)
    $this->inventoryValuationService->processIncomingStock(
        $product, $quantity, $costPerUnit, $date, $sourceDocument
    );
    
    // 3. Update stock quants at destination location
    $this->stockQuantService->applyForIncomingProductLine($productLine);
}
```

---

### Outgoing Stock (Sales)

**Trigger**: When you post an Invoice for storable products

**Action Path**: `ProcessOutgoingStockAction.php`

```
                      ProcessOutgoingStockAction
                              │
    ┌─────────────────────────┴─────────────────────────┐
    ▼                                                   ▼
StockReservationService.consume()          InventoryValuationService
(Decrease quantity at                      (Calculate COGS from layers
 source location)                           + Create journal entries)
```

**What happens step-by-step**:

1. **Create Stock Move** → Records the movement with `move_type = Outgoing`
2. **Calculate COGS** → Determines the cost using:
   - **AVCO**: Uses `Product.average_cost`
   - **FIFO**: Consumes oldest cost layers first
   - **LIFO**: Consumes newest cost layers first
3. **Update StockQuant** → Decreases quantity at warehouse location
4. **Create Journal Entry**:
   - Debit: COGS Expense (expense increases)
   - Credit: Inventory Asset (asset decreases)

**Code Flow**:
```php
// ProcessOutgoingStockAction::execute()
foreach ($stockMove->productLines as $productLine) {
    // 1. Calculate COGS and update cost layers
    $this->inventoryValuationService->processOutgoingStock(
        $product, $quantity, $date, $sourceDocument
    );
}

// 2. Consume reservations and update quants
$this->stockReservationService->consumeForMove($stockMove);
```

---

## How Inventory Counts Are Calculated

The `StockQuantService` is the core service for quantity calculations.

**Path**: `Modules/Inventory/app/Services/Inventory/StockQuantService.php`

### Getting Total Quantity

```php
public function getTotalQuantity(int $companyId, int $productId, ?int $locationId = null): float
{
    $query = StockQuant::where('company_id', $companyId)
        ->where('product_id', $productId);
        
    if ($locationId) {
        $query->where('location_id', $locationId);
    }
    
    return (float) $query->sum('quantity');
}
```

### Getting Available Quantity (for new orders)

```php
public function available(int $companyId, int $productId, ?int $locationId = null): float
{
    // ... query setup ...
    $totalQty = (float) $query->sum('quantity');
    $totalReserved = (float) $query->sum('reserved_quantity');
    
    return $totalQty - $totalReserved;
}
```

### Adjusting Stock (with atomic locking)

```php
public function adjust(int $companyId, int $productId, int $locationId, 
                       float $deltaQty, float $deltaReserved = 0, ?int $lotId = null): StockQuant
{
    return DB::transaction(function () use (...) {
        // Lock the row to prevent race conditions
        $quant = StockQuant::where(...)
            ->lockForUpdate()
            ->first();
        
        $newQty = $quant->quantity + $deltaQty;
        $newReserved = $quant->reserved_quantity + $deltaReserved;
        
        // Validation
        if ($newQty < 0) {
            throw new RuntimeException('Insufficient quantity for adjustment');
        }
        if ($newReserved > $newQty) {
            throw new RuntimeException('Reserved cannot exceed available');
        }
        
        $quant->forceFill([
            'quantity' => $newQty,
            'reserved_quantity' => $newReserved,
        ])->save();
        
        return $quant;
    });
}
```

---

## Where to View Inventory in the UI

Navigate to the **Inventory Cluster** in the main menu:

| Section | What You See | Resource Path |
|---------|--------------|---------------|
| **Stock Quantities** | Current stock by product + location | `StockQuantResource` |
| **Stock Movements** | History of all ins and outs | `StockMoveResource` |
| **Products** | Product catalog with inventory settings | `ProductResource` |
| **Stock Locations** | Your warehouses and locations | `StockLocationResource` |
| **Lots** | Lot tracking for traceability | `LotResource` |
| **Reports** | Valuation, aging, and turnover reports | Various report pages |

### The Stock Quantities Screen

This is your **go-to screen** for "how much inventory do I have?":
- Filter by product or location
- See quantity, reserved, and available
- See which lot (if lot tracking is enabled)

### The Stock Movements Screen

This is your **audit trail**—every movement ever made:
- Filter by date range, product, or location
- See incoming vs outgoing movements
- Track references back to source documents (bills, invoices)

---

## Key Concepts to Remember

### 1. Double-Entry Everything

Every stock movement creates journal entries following double-entry accounting:

| Movement Type | Debit Account | Credit Account |
|--------------|---------------|----------------|
| Incoming | Inventory Asset ↑ | Stock Input Liability ↓ |
| Outgoing | COGS Expense ↑ | Inventory Asset ↓ |
| Adjustment (increase) | Inventory Asset ↑ | Adjustment Account ↓ |
| Adjustment (decrease) | Adjustment Account ↑ | Inventory Asset ↓ |

### 2. Quantity vs Value

Two different tracking systems:

- **StockQuant** → Tracks **physical quantities** (how many units)
- **InventoryCostLayer** + `Product.average_cost` → Tracks **monetary values** (what they cost)

### 3. Reservations

When a sales order is created but not shipped:

```
┌─────────────────────────────────────────────────────────────┐
│ Before Order:    quantity=100, reserved=0,  available=100  │
│ After Order:     quantity=100, reserved=10, available=90   │
│ After Shipping:  quantity=90,  reserved=0,  available=90   │
└─────────────────────────────────────────────────────────────┘
```

- `reserved_quantity` increases when order is placed
- `available_quantity` decreases accordingly
- **But actual `quantity` doesn't change until the order ships!**

### 4. Immutability

Once a movement reaches "Done" status, it **cannot be edited or deleted**. To fix mistakes:
- Create a **reversing movement** (opposite direction)
- Follow the accounting principle of corrections through new entries

### 5. Cost Layer Consumption (FIFO/LIFO)

For FIFO, oldest layers are consumed first:

```
Before Sale (selling 80 units):
┌──────────────────────────────────────────┐
│ Layer 1: 50 units @ $10 (remaining: 50) │
│ Layer 2: 40 units @ $12 (remaining: 40) │
│ Layer 3: 30 units @ $11 (remaining: 30) │
└──────────────────────────────────────────┘

After Sale:
┌──────────────────────────────────────────┐
│ Layer 1: 50 units @ $10 (remaining: 0)  │  ← Fully consumed
│ Layer 2: 40 units @ $12 (remaining: 10) │  ← 30 consumed
│ Layer 3: 30 units @ $11 (remaining: 30) │  ← Untouched
└──────────────────────────────────────────┘

COGS = (50 × $10) + (30 × $12) = $500 + $360 = $860
```

---

## Quick Summary Diagram

```
   VENDOR                          YOUR WAREHOUSE                          CUSTOMER
   ┌─────┐                         ┌─────────────┐                         ┌─────┐
   │     │  ──Incoming (Buy)──▶   │ StockQuant  │  ──Outgoing (Sell)──▶   │     │
   │     │                         │  quantity ↑ │                         │     │
   └─────┘                         └─────────────┘                         └─────┘
       │                                 │                                     │
       ▼                                 ▼                                     ▼
   VendorBill                      StockMove                               Invoice
   creates                         records                                 creates
   movement                        transaction                             movement
       │                                 │                                     │
       ▼                                 ▼                                     ▼
   Journal Entry                   Cost Layers                            Journal Entry
   (Inventory ↑)                   Updated                                (COGS ↑)
```

---

## Key Files Reference

### Models
- `Modules/Inventory/app/Models/StockQuant.php` - Current inventory quantities
- `Modules/Inventory/app/Models/StockMove.php` - Movement records
- `Modules/Inventory/app/Models/StockMoveProductLine.php` - Products in a movement
- `Modules/Inventory/app/Models/StockLocation.php` - Warehouse locations
- `Modules/Inventory/app/Models/InventoryCostLayer.php` - FIFO/LIFO cost layers

### Services
- `StockQuantService.php` - Quantity calculations and adjustments
- `InventoryValuationService.php` - Cost calculations and journal entries
- `StockReservationService.php` - Order reservations

### Actions
- `ProcessIncomingStockAction.php` - Handles incoming stock processing
- `ProcessOutgoingStockAction.php` - Handles outgoing stock processing
- `ConfirmStockMoveAction.php` - Confirms and processes stock moves
- `CreateStockMoveAction.php` - Creates new stock movements

### Enums
- `StockMoveType.php` - Movement types (Incoming, Outgoing, etc.)
- `StockMoveStatus.php` - Status states (Draft, Confirmed, Done)
- `StockLocationType.php` - Location types (Internal, Vendor, Customer)
- `ValuationMethod.php` - FIFO, LIFO, AVCO, Standard

---

## Related Documentation

- [Inventory Management](../User%20Guide/inventory-management.md) - User guide overview
- [Stock Movements](../User%20Guide/stock-movements.md) - Detailed movement guide
- [Inventory System Production Readiness](inventory-system-production-readiness-report.md) - Technical readiness report

---

This documentation covers the core concepts needed to understand and work with the inventory movement system. For specific implementation details, refer to the source files listed above.
