---
title: Manual Journal Entries
icon: heroicon-o-pencil-square
order: 2
---

# Recording Manual Journal Entries

While most accounting records are created automatically, you may need to record manual entries for adjustments, corrections, or non-standard transactions (like depreciation).

---

## When to Use Manual Entries

Use this guide when you need to:
1.  **Correct Mistakes**: Reclassify an expense to a different account.
2.  **Record Depreciation**: Log monthly asset value loss.
3.  **Accruals & Prepayments**: Adjust for income or expenses belonging to different periods.
4.  **Opening Balances**: Record your initial account balances.

---

## Creating a Manual Entry

### 1. Navigate to the Module
Go to **Accounting → Accounting → Journal Entries** and click **Create**.

### 2. Enter Header Information
Fill in the basic details for the entry:

| Field | Description | Example |
| :--- | :--- | :--- |
| **Journal** | The specific "book" for this entry. | *Miscellaneous Operations* |
| **Reference** | A unique ID or description for the adjustment. | *ADJ-2024-001* |
| **Date** | The effective date of the transaction. | *2024-01-31* |

### 3. Add Journal Items (The Double-Entry)
Add lines to the entry. Every entry must have at least two lines, and **Total Debits must equal Total Credits**.

1.  **Select Account**: Choose the GL account affected.
2.  **Partner (Optional)**: Select a vendor or customer if the line relates to one.
3.  **Label**: Enter a clear description for this specific line.
4.  **Debit / Credit**: Enter the amount. 

> [!TIP]
> Use the **Label** field to explain *why* this line is being recorded. This is invaluable during audits.

### 4. Verification and Posting
1.  **Check Balance**: Ensure the "Difference" at the bottom of the table is **0.00**.
2.  **Save as Draft**: Click **Create** to save your work. The entry is now in **Draft** status and has not yet affected your financials.
3.  **Post**: Once reviewed, click **Post**. The entry is now finalized and locked.

---

## Reversing an Incorrect Entry

If you have already **Posted** an entry and discovered a mistake, you cannot edit it. You must reverse it:

1.  Open the Posted Journal Entry.
2.  Click the **Reverse** button in the header.
3.  Choose the reversal date (usually the same as the original or today).
4.  The system creates a new entry with swapped Debit/Credit values to nullify the original.

---

## Related Documentation
- [Automatic Journal Flow](../explanation/automatic-journal-flow.md) - How the system creates entries for you.
- [Common GL Entry Types](../reference/common-gl-entry-types.md) - Quick reference for adjustment patterns.
