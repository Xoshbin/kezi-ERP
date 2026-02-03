---
title: Stock Movement Reference
icon: heroicon-o-information-circle
order: 12
---

# Stock Movement Reference

Technical specifications for stock movement types, statuses, and automated behaviors.

---

## Movement Types

| Type | Purpose | Source Location | Destination Location | Accounting Entry |
| :--- | :--- | :--- | :--- | :--- |
| **Receipt** | Inbound from vendors. | Vendor (Virtual) | Warehouse (Internal) | Debit Inventory, Credit Stock Input |
| **Delivery** | Outbound to customers. | Warehouse (Internal) | Customer (Virtual) | Debit COGS, Credit Inventory |
| **Transfer** | Moving between bins. | Warehouse (Internal) | Warehouse (Internal) | None (Internal) |
| **Adjustment** | Reconciling errors. | Warehouse (Internal) | Inventory Loss (Virtual) | Inventory Adj. Expense |

---

## Workflow Statuses

| Status | Characteristic | Stock Impact | Accounting Impact |
| :--- | :--- | :--- | :--- |
| **Draft** | Fully editable. | None. | None. |
| **Confirmed** | Locked; ready to move. | "Reserved" quantity increases. | None. |
| **Done** | Immutable; completed. | "On Hand" quantity updates. | Journal Entry posted. |

---

## Automatic Movement Triggers

The system automatically generates stock movements in these scenarios:

### 1. Vendor Bill Posting
When a Vendor Bill for **Storable Products** is posted:
- **Movement**: Vendor Location → Default Receipt Location.
- **Reference**: Bill Number (e.g., `BILL-2026-001`).

### 2. Customer Invoice Posting
When a Customer Invoice for **Storable Products** is posted:
- **Movement**: Default Storage Location → Customer Location.
- **Reference**: Invoice Number (e.g., `INV-2026-001`).

---

## System Requirements & Permissions

- **Product Configuration**: Type must be set to "Storable Product".
- **Account Mapping**: Proper Inventory Asset, COGS, and Inventory Adjustment accounts must be set on the product category.
- **Lock Dates**: Movements cannot be created or modified for dates before the Accounting Lock Date.

---

## Related Documentation
- [How-to: Tracking Stock Movements](../how-to/stock-movements.md)
- [Explanation: Inventory Concepts](../explanation/inventory-concepts.md)
- [Reference: Inventory Fields](inventory-fields.md)
