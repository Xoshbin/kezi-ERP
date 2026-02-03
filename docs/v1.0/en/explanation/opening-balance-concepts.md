---
title: Understanding Opening Balance Concepts
description: Deep dive into the accounting principles of opening balances and the role of the Counterpart account.
---

# Explanation: Opening Balance Concepts

When moving your accounting to Kezi, you aren't just starting from zero; you are picking up where your previous system left off. To do this accurately, you must understand how Kezi maintains the "Balance" in your Balance Sheet during migration.

## The Principle of Double-Entry

In double-entry accounting, every entry must have equal debits and credits. When you enter an opening balance for one account (e.g., your Bank account has $10,000), you must record the "other side" of that transaction.

In a perfect migration, you would enter a single massive Journal Entry containing *all* your accounts (Assets, Liabilities, and Equity). The debits and credits would naturally equal each other.

## The Role of the "Opening Balance Equity" Account

In reality, most businesses migrate in stages:
1.  First, they record their **Bank balances**.
2.  Then, they record **Customer Invoices**.
3.  Later, they record **Stock levels**.

Since you aren't entering everything at once, your entries won't balance. This is where the **Opening Balance Equity** (or "Counterpart") account comes in. It acts as a temporary "holding area" for the other side of your entries.

### Example: Migrating in Stages

**Stage 1: Entering Bank Balance**
*   **Debit**: Bank ($10,000)
*   **Credit**: Opening Balance Equity ($10,000)
*   *Result*: Your bank balance is correct, but your Equity shows a $10,000 credit that doesn't "belong" there yet.

**Stage 2: Entering Owner's Capital**
*   **Debit**: Opening Balance Equity ($10,000)
*   **Credit**: Owner's Capital ($10,000)
*   *Result*: The "Opening Balance Equity" account now has a balance of **zero** ($10k credit + $10k debit), and your Owner's Capital is correctly recorded.

## Why use a Counterpart for Invoices?

When you record an old unpaid invoice from last year, Kezi standard behavior would be to credit "Sales Revenue". However, that sale happened last year and was already taxed/recorded in your old system.

By using the **Opening Balance Counterpart** account instead of a Revenue account, you:
1.  Correct your **Accounts Receivable** (the customer owes you).
2.  Avoid inflating your **Current Year Revenue** (the P&L stays clean).
3.  Ensure your **Balance Sheet** is correct.

## The Goal: Zero Balance

The ultimate goal of a successful migration is for the **Opening Balance Equity** account to have a **Zero balance** once all data is entered. 

If this account still has a balance after you’ve finished:
*   **Debit Balance**: You have missing Credits (likely missed a Liability or Equity item).
*   **Credit Balance**: You have missing Debits (likely missed an Asset or Expense item).

---

### Related Articles
- [How-to: Recording Opening Balances](../how-to/recording-opening-balances.md)
- [Tutorial: Setting Up Opening Balances](../tutorials/setting-opening-balances.md)
