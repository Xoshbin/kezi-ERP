---
title: Opening Balance Import Templates
description: Technical specifications and field descriptions for importing opening balances via CSV.
---

# Reference: Opening Balance Import Templates

If you have thousands of records to migrate, manual entry is not feasible. Use this reference to prepare your CSV files for importing opening balances into Kezi.

## General Ledger (Chart of Accounts)

**Target Location**: Accounting > Chart of Accounts > Import

| Field Name | Required | Type | Description |
| :--- | :--- | :--- | :--- |
| `code` | Yes | String | Unique account code (e.g., `110101`). |
| `name` | Yes | String | Descriptive name (e.g., `Bank Al-Mansour`). |
| `type` | Yes | Enum | One of: `asset`, `liability`, `equity`, `revenue`, `expense`. |
| `opening_balance` | Yes | Decimal | The starting amount. |
| `currency_code` | No | String | ISO code (e.g., `USD`, `IQD`). Defaults to base currency. |

## Partner Opening Balances (AR/AP)

**Target Location**: Sales > Invoices > Import OR Purchases > Vendor Bills > Import

To record individual outstanding invoices/bills:

| Field Name | Required | Type | Description |
| :--- | :--- | :--- | :--- |
| `partner_name`| Yes | String | Must match an existing partner name. |
| `date` | Yes | Date | The date of the original transaction. |
| `reference` | Yes | String | Original invoice/bill number. |
| `line_items/description` | Yes | String | Brief description (e.g., "Opening Balance"). |
| `line_items/account` | Yes | Code | **Critical**: Use the "Opening Balance Counterpart" account code. |
| `line_items/price` | Yes | Decimal | Remaining amount to be paid. |

## Inventory Opening Stock

**Target Location**: Inventory > Stock Adjustments > Import

| Field Name | Required | Type | Description |
| :--- | :--- | :--- | :--- |
| `warehouse` | Yes | String | Name of the warehouse location. |
| `product_code`| Yes | String | Unique SKU or code of the product. |
| `quantity` | Yes | Decimal | Amount of stock on hand. |
| `unit_price` | Yes | Decimal | Cost per unit for valuation. |
| `counterpart` | Yes | Code | Use the "Opening Balance Equity" account code. |

---

## Technical Formatting Rules

1.  **Date Format**: Use `YYYY-MM-DD` (e.g., `2024-12-31`).
2.  **Decimals**: Use a dot `.` as a decimal separator (e.g., `1500.50`).
3.  **Encoding**: Save files as **UTF-8 (Comma Delimited)** to ensure Kurdish characters are preserved if applicable.
4.  **Currency**: If importing in foreign currency, ensure the `exchange_rate` is provided or matches the system's rate for that date.

### Related Articles
- [How-to: Recording Opening Balances](../how-to/recording-opening-balances.md)
- [Explanation: Opening Balance Concepts](../explanation/opening-balance-concepts.md)
