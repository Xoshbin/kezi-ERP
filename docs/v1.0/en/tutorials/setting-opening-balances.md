---
title: Setting Up Opening Balances
description: Learn how to transition your financial data from a legacy system to Kezi by entering opening balances.
---

# Tutorial: Setting Up Opening Balances

Welcome to Kezi! One of the most critical steps in starting with a new accounting system is migrating your existing financial data. This tutorial walks you through the process of setting up your **Opening Balances**—the financial snapshot of your business on the day you switch to Kezi.

## The Story: Ahmed's Furniture Shop

Ahmed has been running a furniture shop for three years, keeping his records in a spreadsheet. Today, he’s moving to Kezi. His spreadsheet shows:
- **Cash in Bank**: $15,000
- **Inventory**: $25,000 (stock of chairs and tables)
- **Owed by Customers**: $5,000
- **Owed to Suppliers**: $3,000
- **Initial Capital**: $42,000

Ahmed wants to ensure his "Day 1" in Kezi matches his "Last Day" in Excel.

---

## Prerequisites

Before we start, ensure you have:
1.  Created your **Chart of Accounts** (e.g., Bank, Inventory, Accounts Payable).
2.  Set your **Fiscal Year** start date.
3.  Gathered your closing Trial Balance from your old system.

---

## Step 1: Create the Opening Journal Entry

In Kezi, we use a **Manual Journal Entry** to record the starting point.

1.  Navigate to **Accounting > Journal Entries**.
2.  Click **Create New**.
3.  Set the **Date** to the day *before* your official start date in Kezi (e.g., Dec 31st if you start on Jan 1st).
4.  Set the **Reference** to `Opening Balances 2024`.

---

## Step 2: Record Assets (Debits)

Assets are what you own. In accounting, these have a **Debit** balance.

Ahmed enters his assets:
- **Bank Account**: Debit $15,000
- **Inventory Account**: Debit $25,000
- **Accounts Receivable**: Debit $5,000

---

## Step 3: Record Liabilities and Equity (Credits)

Liabilities (what you owe) and Equity (owner's stake) have a **Credit** balance.

Ahmed enters his credits:
- **Accounts Payable**: Credit $3,000
- **Owner's Capital**: Credit $42,000

---

## Step 4: Balance the Entry

A fundamental rule of double-entry accounting is that **Debits must equal Credits**.

| Account | Debit | Credit |
| :--- | :--- | :--- |
| Bank | $15,000 | |
| Inventory | $25,000 | |
| Accounts Receivable | $5,000 | |
| Accounts Payable | | $3,000 |
| Owner's Capital | | $42,000 |
| **Total** | **$45,000** | **$45,000** |

Kezi will show a green checkmark indicating the entry is balanced. Click **Post**.

---

## Step 5: Verify the Trial Balance

Now that the entry is posted, let's check the result.

1.  Navigate to **Accounting > Reports > Trial Balance**.
2.  Select your start date.
3.  Verify that every account matches your spreadsheet.

## Summary

You have successfully migrated your business's "financial health" into Kezi! All future transactions—sales, purchases, and expenses—will now build upon this accurate foundation.

### What's next?
- [How-to: Recording Partner Opening Balances](../how-to/recording-opening-balances.md)
- [Explanation: Understanding Opening Balance Concepts](../explanation/opening-balance-concepts.md)
