---
title: Automatic Journal Flow
icon: heroicon-o-arrow-path
---

# Automatic Journal Flow

In Kezi , you rarely need to create manual journal entries. Most financial records are generated automatically as a byproduct of your daily business operations.

---

## The "Everything is a Journal" Philosophy

The system is built on a "Double-Entry" foundation. Every time you perform an action that has financial value, the system generates a **Journal Entry** in the background.

For example:
- **Validating a Vendor Bill**: Automatically generates a debit to an expense/asset account and a credit to Accounts Payable.
- **Recording a Customer Payment**: Automatically generates a debit to a Bank/Cash account and a credit to Accounts Receivable.
- **Selling Inventory**: Automatically generates entries for Revenue, Tax, and Cost of Goods Sold (COGS).

---

## Automatic vs. Manual Entries

| Feature | Automatic Entries | Manual Journal Entries |
| :--- | :--- | :--- |
| **Origin** | Triggered by business documents (Bills, Invoices, Payments). | Created directly in the Journal Entries module. |
| **Source Document** | Linked to the original transaction (e.g., Bill #102). | No mandatory source document; uses a Reference field. |
| **Automation** | Accounts, taxes, and amounts are calculated by the system. | You must manually select accounts and ensure Debits = Credits. |
| **Usage** | Standard daily operations. | Adjustments, corrections, depreciation, and opening balances. |

---

## Understanding Journal Entry Statuses

Regardless of whether they are automatic or manual, all journal entries go through a lifecycle:

### 📝 Draft
Draft entries are "candidate" records.
- They **do not affect** your financial reports (Balance Sheet, P&L).
- They can be edited or deleted.
- Automatic entries often start as Drafts when the source document (like a Vendor Bill) is in "Draft" or "Awaiting Approval" status.

### ✅ Posted
Posted entries are finalized records.
- They update your General Ledger immediately.
- They are **locked** for editing to ensure an audit trail.
- To change a Posted entry, you must **Reverse** it to create a counter-entry.

---

## Why Manual Entries Still Exist

Manual entries are the "safety valve" of the accounting system. They are used for:
1.  **Adjusting Entries**: Year-end adjustments for accruals or prepayments.
2.  **Depreciation**: Recording the loss of value in fixed assets over time.
3.  **Corrections**: Moving a balance from an incorrectly selected account to the correct one.
4.  **Opening Balances**: Bringing in historical data when you first start using the system.

---

## Related Documentation
- [Manual Journal Entries](../how-to/journal-entries.md) - How to record adjustments.
- [Common GL Entry Types](../reference/common-gl-entry-types.md) - Reference for accounting patterns.
- [Vendor Bills](../explanation/understanding-vendor-bills.md) - How bills generate journal entries.
