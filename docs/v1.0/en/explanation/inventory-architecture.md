---
title: Inventory Architecture
icon: heroicon-o-server-stack
order: 10
---

# Explanation: Inventory Architecture

This technical guide explains the internal architecture of the inventory system, focusing on data models, the "Move" concept, and synchronous processing.

---

## The Big Picture

The inventory system answers four key questions:

1.  **How much stuff you have** → Stock Quantities (`StockQuant`)
2.  **Where that stuff is** → Locations (`StockLocation`)
3.  **How stuff moves around** → Stock Movements (`StockMove`)
4.  **What that stuff cost you** → Valuation (`InventoryCostLayer`, `Product.average_cost`)

---

## Key Building Blocks (The Models)

### 1. StockQuant - "The Counter"
**Namespace**: `Modules\Inventory\App\Models\StockQuant`

This is the "current inventory snapshot." It tracks the quantity of a specific product at a specific location:

| Product | Location | Quantity | Reserved | Available |
| :--- | :--- | :--- | :--- | :--- |
| iPhone 15 | Warehouse A | 100 | 10 | 90 |

*   **Computed Value**: `Available = quantity - reserved_quantity`

### 2. StockMove - "The Transaction Record"
**Namespace**: `Modules\Inventory\App\Models\StockMove`

Every time inventory moves, there's a `StockMove` record.
*   **Status Workflow**: `Draft` → `Confirmed` → `Done`

### 3. StockLocation - "The Places"
**Namespace**: `Modules\Inventory\App\Models\StockLocation`

*   **Internal**: Your warehouses.
*   **Vendor**: Where you buy from (Virtual).
*   **Customer**: Where you sell to (Virtual).
*   **Inventory Adjustment**: For corrections (Virtual).

---

## Synchronous Processing Architecture

When a Stock Move is confirmed (Status → `Done`), the system processes inventory and accounting updates **synchronously** within the same database transaction.

### Incoming Stock Flow
```
User Confirms Move
    │
    ▼
StockMoveObserver::updated()
    │
    ▼ (Same DB Transaction)
[ProcessIncomingStockAction]
    ├─ Extract Cost (from PO/Bill)
    ├─ Update Valuation (Cost Layers / AVCO)
    ├─ Update Quantities (StockQuant)
    └─ Create Journal Entry (Asset vs Input)
```

### Outgoing Stock Flow
```
User Confirms Move
    │
    ▼
StockMoveObserver::updated()
    │
    ▼ (Same DB Transaction)
[ProcessOutgoingStockAction]
    ├─ Calculate COGS (FIFO/LIFO/AVCO)
    ├─ Update Valution (Consume Layers)
    ├─ Update Quantities (Decrease StockQuant)
    └─ Create Journal Entry (COGS vs Asset)
```

**Why Synchronous?**
*   **Data Integrity**: Inventory quantities and accounting values must always match.
*   **Error Handling**: If cost validation fails (e.g., negative stock in strict mode), the entire transaction rolls back, preventing "ghost" inventory states.

---

## Observers & Actions

*   **`StockMoveObserver`**: The entry point for movement logic. It detects the status change to `Done` and delegates to the appropriate Action.
*   **`StockQuantService`**: Handles the atomic locking and updating of quantity counters (`lockForUpdate()`).
*   **`InventoryValuationService`**: Manages the complex math of FIFO buckets and Moving Average calculations.

---

## See Also
*   [Reference: Inventory Fields](../reference/inventory-fields.md)
