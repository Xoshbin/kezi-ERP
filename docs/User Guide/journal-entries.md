---
title: Journal Entries
icon: heroicon-o-book-open
order: 2
---

# Journal Entries: Manual Accounting Records

This guide explains how **Journal Entries** work in the system. While most entries are created automatically (like creating an invoice), sometimes you need to record transactions manually to keep your books balanced.

---

## What is a Journal Entry?

Think of a **Journal Entry** as the raw data of accounting. It's a record of a financial transaction that affects at least two accounts.

**Why does this matter?**
1.  **Correction**: Fix mistakes or adjust balances.
2.  **Non-standard transactions**: Record things like depreciation, payroll, or bank fees manually if needed.
3.  **Opening Balances**: Bring in data from previous systems.

---

## Where to Find It

Navigate to: **Accounting → Accounting → Journal Entries**

You will see a list of all journal entries (both automatic and manual).

---

## Creating a Manual Journal Entry

Let's walk through creating a new manual entry.

### Step 1: Start Fresh

Navigate to **Accounting → Journal Entries → Create**

You'll see a form with these header fields:

| Field | Description | Example |
|-------|-------------|---------|
| **Journal** | The book this entry belongs to (e.g., Miscellaneous, Bank) | "Miscellaneous Operations" |
| **Reference** | A unique reference for this transaction | "ADJ/2024/001" |
| **Date** | The date of the transaction | Today's date |
| **Currency** | Transaction currency | "USD" |

### Step 2: Add Journal Items (Lines)

This is where you define the "Double-Entry". You must add at least two lines.

| Field | Description |
|-------|-------------|
| **Account** | The general ledger account (e.g., 5001 Office Expense) |
| **Partner** | (Optional) The customer or vendor associated with this line |
| **Label** | Description of this specific line |
| **Debit** | Amount increasing assets/expenses or decreasing liabilities/equity |
| **Credit** | Amount increasing liabilities/equity/income or decreasing assets |

> [!IMPORTANT]
> **The Double-Entry Rule**: Total **Debit** must equal Total **Credit**. The system will not allow you to post an unbalanced entry.

### Step 3: Review and Save

1.  Check that the **Differences** amount at the bottom is **0.00**.
2.  Click **Create** to save as a **Draft**.

---

## Understanding Statuses

Your journal entry goes through these stages:

┌─────────┐      ┌─────────┐
│  Draft  │ ──▶ │ Posted  │
└─────────┘      └─────────┘
    📝              ✅

### 📝 Draft
- The entry is saved but **does not affect** your financial reports yet.
- You can still edit or delete it.
- Useful for preparing entries before finalizing.

### ✅ Posted
- The entry is "official" and updates your General Ledger.
- It is now locked and cannot be deleted (to ensure audit trails).
- Appears in your Trial Balance and other reports.

---

## Reversing an Entry

If you made a mistake in a **Posted** entry, you cannot simply delete it. Instead, you must **Reverse** it.

1.  Open the Posted entry.
2.  Click the **Reverse Entry** button (if available) or create a new entry with opposite Debit/Credit values.
3.  This creates a "cancellation" record, keeping your audit history clean.

---

## Troubleshooting

### Common Questions

**Q: Why can't I post my entry?**
A: Check if your debits and credits are equal. The system requires a balanced entry. Also, check if the date is within a **Locked Period**.

**Q: Can I import entries?**
A: Yes! Use the **Import** action on the list page to upload a CSV/Excel file of journal entries.

**Q: Where is the money coming from?**
A: Remember:
- **Debit** = Asset increases (Money coming in/Expense incurred)
- **Credit** = Liability/Income increases (Source of funds)

---

## Related Documentation

- [Chart of Accounts](chart-of-accounts.md) - Understanding your accounts
- [General Ledger](general-ledger-report.md) - Seeing the big picture
