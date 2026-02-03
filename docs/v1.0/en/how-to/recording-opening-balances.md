---
title: Recording Opening Balances
description: Step-by-step instructions on recording starting balances for accounts, partners, and inventory.
---

# How-to: Recording Opening Balances

Recording opening balances is the process of entering your starting financial data when you first begin using Kezi.

## Recording General Account Balances

1.  Go to **Accounting > Journal Entries > Create**.
2.  Select an **Opening Balance Journal** (or use Miscellaneous).
3.  Set the **Date** to the day before your go-live date.
4.  Add lines for each account in your Trial Balance:
    *   **Assets** (Cash, Bank, Fixed Assets): Enter in **Debit**.
    *   **Liabilities** (Loans, Taxes Owed): Enter in **Credit**.
    *   **Equity** (Retained Earnings, Capital): Enter in **Credit**.
5.  **Balance the entry** against the [Opening Balance Equity](../explanation/opening-balance-concepts.md) account if you are entering balances incrementally.

## Recording Partner Balances (AR/AP)

To maintain a sub-ledger for customers and vendors, do not just enter a lump sum in the GL.

### For Outstanding Customer Invoices:
1.  Go to **Sales > Invoices > Create**.
2.  Select the **Partner**.
3.  Enter the details of the original invoice.
4.  Set the **Account** to the "Opening Balance Counterpart" account instead of a Revenue account to avoid double-counting income in the current year.
5.  Validate the invoice.

### For Outstanding Vendor Bills:
1.  Go to **Purchases > Vendor Bills > Create**.
2.  Select the **Vendor**.
3.  Enter the bill details.
4.  Set the **Expense Account** to the "Opening Balance Counterpart" account.
5.  Validate the bill.

## Recording Inventory Opening Stock

1.  Go to **Inventory > Stock Adjustments > Create**.
2.  Select the **Location** (Warehouse).
3.  Add products and their **Starting Quantities**.
4.  Set the **Valuation Price** to the cost at which you purchased them.
5.  Set the **Counterpart Account** to "Opening Balance Equity".
6.  **Validate** the adjustment to update your stock levels and accounting.

---

## Troubleshooting

### Entry is not balancing
Ensure that `Total Debits = Total Credits`. If you are missing one side of the equation, use the **Opening Balance Equity** account as a temporary placeholder.

### Duplicate Revenue/Expense
When recording old invoices or bills, ensure the line items point to the **Opening Balance** account, not your standard Sales or Expense accounts, so your current year's Profit & Loss stays accurate.

### Related Articles
- [Tutorial: Setting Up Opening Balances](../tutorials/setting-opening-balances.md)
- [Explanation: Opening Balance Concepts](../explanation/opening-balance-concepts.md)
- [Reference: Import Templates](../reference/opening-balance-import-template.md)
