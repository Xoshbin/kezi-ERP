---
title: Adjustment Documents
icon: heroicon-o-document-duplicate
order: 6
---

# Adjustment Documents: Corrections & Manual Entries

This guide explains how to use Adjustment Documents to make manual corrections to your General Ledger. Whether you need to fix a mistake, reclassify an expense, or record opening balances, this tool gives you direct control over your accounting records.

---

## What are Adjustment Documents?

Think of an **Adjustment Document** as a manual override for your accounting records. Most entries in the system are created automatically (like when you post an invoice), but sometimes you need to make corrections by hand.

**Common reasons to use this:**
1.  **Corrections**: You booked an expense to "Office Supplies" but it should have been "Computer Equipment".
2.  **Accruals**: Recording expenses that happened but haven't been billed yet.
3.  **Opening Balances**: Setting up your initial account balances when starting with the system.
4.  **Depreciation**: Manually recording asset depreciation (if not automated).

---

## Where to Find It

Navigate to: **Accounting → Adjustment Documents**

You'll see a list of all manual adjustments, showing their status (Draft, Posted, or Cancelled).

---

## How to Create an Adjustment

### Step 1: Start a New Adjustment

1.  Go to **Accounting → Adjustment Documents**.
2.  Click **Create Adjustment Document**.

### Step 2: Document Details

Fill in the header information:

| Field | Description | Example |
|-------|-------------|---------|
| **Currency** | The currency for this transaction | USD |
| **Type** | The kind of adjustment | Standard, Opening Balance |
| **Reference** | A unique identifier for tracking | ADJ-2024-JAN-05 |
| **Date** | The accounting date for the entry | 2024-01-31 |
| **Reason** | Why are you making this change? | "Reclassifying printer purchase" |

### Step 3: Add Line Items

This is where you define the debits and credits.

> [!IMPORTANT]
> A valid journal entry must balance: **Total Debits = Total Credits**.

1.  Click **Add Line Item**.
2.  **Product** (Optional): If this relates to a specific product.
3.  **Description**: Describe this specific line (e.g., "Correcting wrong category").
4.  **Quantity** & **Price**: Used to calculate the amount.
5.  **Account**: The General Ledger account to hit.
6.  **Tax** (Optional): If tax needs to be adjusted.

**Example: Reclassifying $500 from Office Supplies to Equipment**

| Description | Account | Debit | Credit |
|-------------|---------|-------|--------|
| Move to Equipment | **Computer Equipment** | $500.00 | $0.00 |
| Remove from Supplies | **Office Supplies** | $0.00 | $500.00 |

### Step 4: Review and Post

1.  Check that your totals balance.
2.  Review the **Reason** to ensure it's clear for future audits.
3.  Click **Create** (to save as Draft) or **Create & Post** (to finalize).

---

## Understanding Statuses

┌─────────┐      ┌─────────┐      ┌─────────┐
│  Draft  │ ──▶ │ Posted  │ ──▶ │Cancelled│
└─────────┘      └─────────┘      └─────────┘
    📝              ✅              ❌

-   **📝 Draft**: You can edit everything. No impact on the General Ledger yet.
-   **✅ Posted**: The entry is locked and recorded in the General Ledger. Affects reports.
-   **❌ Cancelled**: The entry is voided and has no effect.

---

## Common Scenarios

### Scenario 1: Reclassifying an Expense

**The Situation**: You noticed a vendor bill for $200 was recorded under "Meals & Entertainment" but it was actually for "Office Supplies". The bill is already paid and closed.

**What to do**:
1.  Create a new Adjustment Document.
2.  **Debit**: Office Supplies ($200) -> Increases the correct expense account.
3.  **Credit**: Meals & Entertainment ($200) -> Decreases the wrong expense account.
4.  **Post** the document.

### Scenario 2: Recording Opening Balances

**The Situation**: You are moving to this system from Excel. You have $10,000 in the bank and $5,000 in Capital.

**What to do**:
1.  Create an Adjustment Document with Type "**Opening Balance**".
2.  **Debit**: Bank Account ($10,000).
3.  **Credit**: Owners Capital ($5,000).
4.  **Credit**: Retained Earnings ($5,000) (Assumed difference).
5.  **Post** the document.

---

## Best Practices

### ✅ Be Descriptive
Always write a clear **Reason**. Six months from now, you won't remember why you moved that $50. "ADJ-001" tells you nothing; "Correction for Inv-2023-005 misnamed category" tells you everything.

### 🔒 Post with Caution
Once posted, an adjustment affects your financial statements immediately. Double-check your accounts and amounts before clicking Post.

### 📅 Watch the Date
The **Date** field determines which fiscal period the specific entry falls into. Be careful not to post adjustments into a closed period if your company policy forbids it.

---

## Troubleshooting

### "Unbalanced Entry" Error
The system will not let you post if Debits do not equal Credits. Check your line items and ensure the totals match exactly.

### Cannot Edit a Posted Document
This is by design to maintain audit trails. If a posted adjustment is wrong:
1.  **Cancel** it (if allowed).
2.  Or create a **new** adjustment that reverses the mistake (Swap the Debits and Credits).
