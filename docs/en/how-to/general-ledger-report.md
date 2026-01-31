---
title: General Ledger Report
icon: heroicon-o-book-open
order: 1
---

# General Ledger Report: The Complete History

This guide explains how to use the General Ledger report to see the detailed history of your accounts. While the Trial Balance summarizes your accounts, the General Ledger shows every single transaction that got them there.

---

## What is the General Ledger?

The **General Ledger** is the "book of final entry." It contains the detailed record of every transaction posted to every account in your system.

**Why does this matter?**
1.  **Detail**: It reveals exactly *why* an account has a certain balance.
2.  **Investigation**: It's the primary tool for finding mistakes (e.g., "Why is my Office Expense so high? Oh, I see five transactions for new furniture").
3.  **Audit Trail**: It provides a permanent record of all financial movements.

---

## Where to Find It

Navigate to: **Accounting → Reports → General Ledger**

---

## How to Use the Report

### 1. Set Your Filters
The report is powerful because you can filter it to see exactly what you need.

| Filter | Description |
|--------|-------------|
| **Start Date** | The beginning of the period you want to review (defaults to start of current month). |
| **End Date** | The end of the period (defaults to end of current month). |
| **Accounts** | Leave empty to see ALL accounts, or search and select specific ones (e.g., "Cash", "Sales"). |

### 2. Generate the Report
Click the **Generate Report** button (Look for the <heroicon-o-play class="w-4 h-4 inline"/> icon).

---

## Understanding the Columns

The report groups transactions by **Account**. For each account, you'll see a header with:
-   **Account Code & Name**
-   **Opening Balance**: The value of the account *before* your selected Start Date.
-   **Closing Balance**: The value of the account *after* all listed transactions.

Inside each account, you'll see these transaction lines:

| Column | Description |
|--------|-------------|
| **Date** | When the transaction happened. |
| **Reference** | The document number (e.g., `INV/2024/0001`, `BILL/2024/005`). |
| **Description** | A brief note about what the transaction was. |
| **Contra Account** | The *other* account affected by this transaction (helping you understand the double-entry). |
| **Debit** | Amount added to Assets/Expenses or removed from Liabilities/Income. |
| **Credit** | Amount added to Liabilities/Income or removed from Assets/Expenses. |
| **Balance** | The running balance of the account after this specific line. |

---

## Common Scenarios

### Scenario 1: Investigating a Suspicious Balance
**Situation**: Your "Travel Expense" account shows a balance of $5,000, which seems too high for this month.
**Action**:
1.  Run the General Ledger for the "Travel Expense" account.
2.  Scan the **Description** and **Amount** columns.
3.  **Result**: You find a misclassified transaction for $2,000 that should have been "Software Subscription." You can now correct it.

### Scenario 2: Reconciling a Specific Bank Account
**Situation**: You want to match your system's "Bank USD" account transactions with your actual bank statement.
**Action**:
1.  Filter by the "Bank USD" account.
2.  Set the dates to match your bank statement period.
3.  **Result**: compare the **Running Balance** line-by-line with your bank statement to find any missing or extra entries.

### Scenario 3: Preparing for Auditor Review
**Situation**: An auditor asks to see all transactions related to "Shareholder Capital" for the year.
**Action**:
1.  Filter by "Shareholder Capital".
2.  Set the date range to the full fiscal year.
3.  **Result**: A clean list of every capital injection or withdrawal, ready for review.

---

## Best Practices

### 🔍 Drill Down
Use the references! If you see a transaction `INV/2024/001` that looks odd, you can search for that Invoice ID in the system to see the original document.

### 📅 Watch the Opening Balance
If you run the report for *March*, the **Opening Balance** represents everything that happened *up to February 28th*. If the Opening Balance looks wrong, the error happened in a previous period.

### 🧹 Keep it Clean
Regularly reviewing key accounts (checking Cash, Accounts Receivable, Accounts Payable) in the General Ledger prevents end-of-year headaches.

---

## Related Documentation

-   [Trial Balance Report](trial-balance-report.md) - A high-level summary of these balances
-   [Journal Entries](../Developers/journal_entry_flow_report.md) - How these lines are created
-   [Balance Sheet](balance-sheet.md) - The financial position report
