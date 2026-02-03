---
title: Understanding Inventory Concepts
icon: heroicon-o-light-bulb
order: 1
---

# Explanation: Inventory Concepts

This guide provides a deep dive into the core concepts that power the Kezi ERP inventory system, explaining "why" things work the way they do.

---

## What is Inventory Management?

Inventory management is a comprehensive system that tracks, values, and manages all aspects of your stock throughout its lifecycle, from purchase to sale.

It answers four fundamental questions:
1.  **How much** do we have? (Quantity)
2.  **Where** is it? (Location)
3.  **What** is it worth? (Valuation)
4.  **When** does it need replenishing? (Reordering)

---

## Double-Entry Inventory

Just like double-entry accounting, modern inventory systems use a double-entry method for stock. **Nothing disappears; it only moves.**

*   **No "Lost" Items**: Instead of reducing stock quantity directly, you move items from "Warehouse" to "Scrap Location" or "Customer Location".
*   **Audit Trail**: Every change in inventory is a "Move" record (`StockMove`) from a Source Location to a Destination Location.

| Movement Type | Source | Destination |
| :--- | :--- | :--- |
| **Receipt** | Vendor Location | Your Warehouse |
| **Delivery** | Your Warehouse | Customer Location |
| **Internal Transfer** | Shelf A | Shelf B |
| **Inventory Loss** | Your Warehouse | Inventory Loss (Virtual) |

---

## Valuation Methods Explained

How do you calculate the cost of the goods you sold? Kezi supports standard accounting methods:

### 1. FIFO (First In, First Out)
*   **Concept**: The first items purchased are the first ones sold.
*   **Best For**: Perishable goods (food, medicine) or when costs are rising.
*   **Mechanism**: The system tracks "Cost Layers". If you bought 10 units @ $100 and then 10 units @ $120, the first 10 sold will have a COGS of $100 each.

### 2. LIFO (Last In, First Out)
*   **Concept**: The most recently purchased items are sold first.
*   **Best For**: Non-perishable goods (coal, sand) or tax strategies in inflation.
*   **Mechanism**: The system consumes the newest Cost Layer first.

### 3. AVCO (Average Cost)
*   **Concept**: The cost is the weighted average of all units in stock.
*   **Best For**: Commodities, identical items mixed together.
*   **Mechanism**: Recalculated after every receipt: `(Old Value + New Value) / Total Quantity`.

### 4. Standard Price
*   **Concept**: You define a fixed cost per unit manually.
*   **Best For**: Manufacturing with standard costing or stable prices.
*   **Mechanism**: Variances between Purchase Price and Standard Price are posted to a Price Difference Account.

---

## Tracking Dimensions

### Lots vs. Serial Numbers
*   **Lots**: A batch of products identified by a single code (e.g., "Batch 101" of Paint). Used for traceability and expiration dates.
*   **Serial Numbers**: A unique code for *one single unit* (e.g., "SN-59283" of a Laptop). Used for warranty and strict theft control.

### Stock Quantities
The system tracks three numbers for every product/location pair:
1.  **On Hand**: What physically exists in the bin.
2.  **Reserved**: What is physically there but promised to an outgoing order.
3.  **Available**: `On Hand - Reserved`. This is what can be sold to new customers.

---

## Reordering Strategies

The system helps you maintain optimal stock levels:

*   **Reordering Rules (Min/Max)**: When stock falls below "Min", the system prompts a purchase to reach "Max".
*   **MTO (Make to Order)**: Do not hold stock. Only buy/make when a customer orders it.

---

## Integration with Accounting

Inventory is strictly tied to the General Ledger:

*   **Receipts** debit **Inventory Asset** and credit **Stock Input Account**.
*   **Deliveries** debit **COGS** and credit **Inventory Asset**.
*   **Landed Costs**: Freight/Duty fees can be added to the inventory value of the received goods rather than expensed immediately.

---

## Related Documentation

*   [How-to: Managing Stock](../how-to/managing-stock.md)
*   [Reference: Inventory Fields](../reference/inventory-fields.md)
*   [Technical: Architecture](../explanation/inventory-architecture.md)
