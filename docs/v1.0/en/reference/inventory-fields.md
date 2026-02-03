---
title: Inventory Fields Reference
icon: heroicon-o-table-cells
order: 1
---

# Reference: Inventory Fields & Enums

This reference lists the key data fields, enumerations, and configurations used in the Inventory module.

---

## 1. Product Configuration Fields

When configuring a Product for inventory, these fields determine behavior:

| Field | Options | Description |
| :--- | :--- | :--- |
| **Product Type** | `Storable Product` | Tracks stock counts. |
| | `Consumable` | Assumes stock is always available (no tracking). |
| | `Service` | No physical presence. |
| **Valuation Method** | `FIFO` | First In, First Out usage. |
| | `LIFO` | Last In, First Out usage. |
| | `AVCO` | Weighted Average Cost. |
| | `Standard Price` | Manual fixed cost. |
| **Tracking** | `No Tracking` | Standard quantity only. |
| | `By Lots` | Batches of product. |
| | `By Unique Serial` | Individual unit IDs. |

---

## 2. Stock Move Types

Defined in `StockMoveType` Enum:

| Type | Symbol | Description |
| :--- | :--- | :--- |
| **Incoming** | 📥 | Vendor → Warehouse (Increases Asset) |
| **Outgoing** | 📤 | Warehouse → Customer (Expenses COGS) |
| **Internal** | 🔄 | Warehouse A → Warehouse B (No Accounting change) |
| **Adjustment**| ⚖️ | Correction of physical count |

---

## 3. Stock Location Types

Defined in `StockLocationType` Enum:

| Type | Nature | Purpose |
| :--- | :--- | :--- |
| **View** | Virtual | A folder to group other locations (cannot store items). |
| **Internal** | Physical | A physical space you own. |
| **Customer** | Virtual | Represents the customer's property. |
| **Vendor** | Virtual | Represents the vendor's property. |
| **Inventory Loss**| Virtual | Counterpart for lost/found items. |
| **Production** | Virtual | Counterpart for manufacturing consumption. |

---

## 4. Accounting Entries

The system generates these Journal Entries automatically:

| Transaction | Debit | Credit |
| :--- | :--- | :--- |
| **Purchase Receipt** | Inventory Asset | Stock Input Account (Interim) |
| **Delivery Order** | COGS Account | Inventory Asset |
| **Inventory Gain** | Inventory Asset | Stock Adjustment Gain |
| **Inventory Loss** | Stock Adjustment Loss | Inventory Asset |

---

## 5. Reports

| Report | Purpose |
| :--- | :--- |
| **Stock On Hand** | Current Qty per Location. |
| **Inventory Valuation** | Value of current stock ($). |
| **Product Moves** | Traceability history. |
| **Stock Aging** | Breakdown of stock by age (0-30, 30-60...). |
