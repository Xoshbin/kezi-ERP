---
title: Managing Stock
icon: heroicon-o-cube
order: 4
---

# How-to: Managing Stock

This guide covers the practical day-to-day operations of managing your inventory quantities, viewing levels, and creating manual movements.

---

## Viewing Current Stock

To see what you currently have on hand:

1.  Navigate to **Inventory > Reporting > Stock Quantities**.
2.  The list view shows:
    *   **Product**: The item name.
    *   **Location**: Where it is stored.
    *   **Quantity**: Setup physical count.
    *   **Reserved**: Quantity locked for pending orders.
    *   **Available**: `Quantity - Reserved`.

**Tip**: Use the **Filters** to group by Location or Product Category.

---

## Creating a Manual Stock Movement

Sometimes you need to move stock without a Purchase or Sales Order (e.g., correcting an error or internal rearrangement).

1.  Navigate to **Inventory > Operations > Moves**.
2.  Click **Create Stock Move**.
3.  **Basic Information**:
    *   **Product**: Select the item.
    *   **From Location**: Where the item is now.
    *   **To Location**: Where it is going.
    *   **Quantity**: How many to move.
4.  **Confirming**:
    *   Click **Save**.
    *   Click **Mark as Done** (or Validate) to execute the move immediately.

---

## Adjusting Inventory (Cycle Counts)

To correct the system quantity to match physical reality:

1.  Navigate to **Inventory > Operations > Physical Inventory**.
2.  Click **Create**.
3.  Select the **Product** or **Location** to count.
4.  Enter the **Counted Quantity** (Real world count).
5.  The system calculates the **Difference**.
6.  Click **Validate Inventory**.
    *   *Result*: The system creates an "Adjustment" move to reconcile the difference.

---

## Managing Lots & Serial Numbers

If your product tracks lots:

1.  **Creation**: When receiving goods, you will be prompted to enter a **Lot Number** (or assign one automatically).
2.  **Tracing**: Go to **Inventory > Products > Lots/Serial Numbers**.
    *   Click a Lot Number to see its history (Traceability Report), showing everywhere it has been.

---

## Configuring Products for Inventory

To ensure a product is tracked:

1.  Go to **Inventory > Products > Products**.
2.  Open the Product.
3.  In the **General Information** tab, set **Product Type** to `Storable Product`.
4.  (Optional) In the **Inventory** tab, set:
    *   **Track Inventory**: By Lots or Unique Serial Number.
    *   **Logistics**: Weight and Volume.
