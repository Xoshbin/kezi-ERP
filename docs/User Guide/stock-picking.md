---
title: Stock Picking
icon: heroicon-o-clipboard-document-list
order: 13
---


This guide explains how to manage stock movements using Stock Pickings (Receipts, Delivery Orders, and Internal Transfers).

---

## Overview

**Stock Picking** is the core record for moving inventory in the system. It represents a "Pick List" or "Transfer Document" that instructs warehouse staff to move goods from one location to another.

### Types of Pickings
1.  **Receipts (Incoming):** Receiving goods from vendors. Increases your physical stock.
    *   *From:* Vendor Location -> *To:* Warehouse
2.  **Delivery Orders (Outgoing):** Shipping goods to customers. Decreases your physical stock.
    *   *From:* Warehouse -> *To:* Customer Location
3.  **Internal Transfers:** Moving goods between internal locations (e.g., Warehouse to Shelf).
    *   *From:* Internal Location 1 -> *To:* Internal Location 2

---

## The Workflow

All pickings follow a standard lifecycle:

1.  **Draft:** The picking is created but not yet finalized. You can edit products and quantities.
2.  **Confirmed:** The planned movement is locked.
3.  **Assigned:** The system checks stock availability (for Deliveries/Transfers).
    *   If stock is available, it reserves the items.
4.  **Done (Validated):** The movement is physically completed and stock levels are updated.

---

## Goods Receipt Note (GRN) from Purchase Orders

When you confirm a Purchase Order with storable products, the system automatically creates a **Draft Goods Receipt Note (GRN)** — a special type of Receipt picking linked to your PO.

### Automatic GRN Creation

1. You confirm a Purchase Order containing storable products.
2. The system creates a draft `StockPicking` of type **Receipt**.
3. Stock Moves are created for each storable product line.
4. The GRN is linked to the PO via `purchase_order_id`.

### Validating the GRN

When goods physically arrive:

1. Navigate to **Inventory > Operations > Transfers** and find the GRN linked to your PO.
2. Verify quantities match what was received.
3. Click **Validate** to confirm receipt.
4. Stock quantities are updated and the PO status changes to **Partially Received** or **Fully Received**.

### Three-Way Matching

When you create a Vendor Bill linked to a Purchase Order, the system performs **three-way matching**:

| Document | What is Checked |
|----------|-----------------|
| **Purchase Order** | What was ordered (quantities, prices) |
| **GRN** | What was physically received |
| **Vendor Bill** | What the vendor is charging |

Matching statuses:
- ✅ **Fully Matched** — All quantities received and match the bill
- ⚠️ **Pending Receipt** — Goods not yet received (blocks posting in strict mode)
- ⚠️ **Partially Received** — Some goods received, more expected
- ❌ **Quantity Mismatch** — Bill quantity differs from received
- ❌ **Price Mismatch** — Bill price differs from PO price

---

## 1. Receiving Goods (Receipts)

Use this when your vendor delivers products you ordered.

1.  Navigate to **Inventory > Operations > Transfers** (or created automatically from a Purchase Order).
2.  Open the **Receipt**.
3.  **Check Quantities:** Verify the "Planned Quantity" matches what the vendor sent.
4.  **Validate:** Click the **Validate** button.
    *   **Partial Receipt:** If you received *less* than planned, enter the actual quantity received.
    *   The system will ask if you want to create a **Backorder** for the missing items.
        *   **Create Backorder:** Choose this if the vendor will send the rest later.
        *   **No Backorder:** Choose this if the vendor will NOT send the rest (cancels remaining qty).

---

## 2. Delivering Goods (Delivery Orders)

Use this to ship products to customers.

1.  Navigate to **Inventory > Operations > Transfers** (or created automatically from a Sales Order).
2.  Open the **Delivery Order**.
3.  **Check Availability:** Ensure the state is **Assigned** (Ready). If it says "Confirmed" (Waiting), you may not have enough stock.
4.  **Assign Lots (Optional):** If the product is tracked by lots/serial numbers, click the "Detailed Operations" or assign icon to select which specific lots you are shipping.
5.  **Validate:** Click **Validate** to Confirm shipment.
    *   Stock is deducted from your inventory.
    *   A **Journal Entry** is automatically created to record the Cost of Goods Sold (COGS).

---

## 3. Internal Transfers

Use this to move items inside your own warehouse (e.g., receiving dock to storage shelf).

1.  Create a new **Transfer**.
2.  **Operation Type:** Select "Internal Transfer".
3.  **Locations:**
    *   **Source Location:** Where the item is now.
    *   **Destination Location:** Where you are moving it to.
4.  **Add Items:** Select products and quantities.
5.  **Confirm** and **Validate**.
    *   **Note:** Internal transfers update quantities but do **not** generate accounting Journal Entries (as the overall inventory value hasn't changed).

---

## Backorders Explained

When you validate a picking for *less* than the planned quantity, the system allows you to create a **Backorder**.

*   **Result:**
    1.  The current picking is marked **Done** for the amount you processed.
    2.  A **NEW** picking is created for the remaining amount.
    3.  This new picking stays in "Assigned" or "Confirmed" state until you are ready to process the rest.

---

## Troubleshooting

*   **"Check Availability" fails:** You likely don't have enough stock in the Source Location. Check your "Stock on Hand" report.
*   **Duplicate Entry Error:** Ensure you are not double-clicking Validate.
*   **Accounting Entries:** Receipts debit Inventory; Deliveries debit COGS. Internal transfers do neither.
