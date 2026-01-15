---
title: Trial Balance Report
icon: heroicon-o-scale
order: 1
---

# Trial Balance Report: Your Financial Sandbox

This guide explains how to use the Trial Balance report to check your accounting accuracy. Whether you're an accountant preparing for month-end or a business owner checking your numbers, this report is your starting point.

---

## What is a Trial Balance?

Think of the **Trial Balance** as a "health check" for your accounting. It lists every single account in your system (like Cash, Sales, Expenses) and shows their current balance.

**Why does this matter?**
1. **Accuracy**: In double-entry accounting, total Debits must ALWAYS equal total Credits. This report proves it.
2. **Review**: It's the best place to spot errors (e.g., a negative balance in an expense account).
3. **Closing**: You need this to verify your numbers before generating an Income Statement or Balance Sheet.

---

## Where to Find It

Navigate to: **Accounting → Reports → Trial Balance**

> **💡 Tip**: You can also find it under the "Reporting" tab in your main dashboard if you have shortcuts enabled.

---

## How to Use the Report

### 1. Set Your Date
The most important filter is the **As of Date**.

- **Default**: Today's date.
- **Why change it?**: If you want to see what your books looked like at the end of last month, change this to the last day of that month (e.g., `2023-12-31`).

### 2. Generate the Report
Click the **Generate Report** button (Look for the <heroicon-o-play class="w-4 h-4 inline"/> icon).

---

## Understanding the Columns

The report displays a table with the following information:

| Column | Description | Example |
|--------|-------------|---------|
| **Account Code** | The unique number for the account | `101000` |
| **Account Name** | What the account is called | `Cash on Hand` |
| **Debit** | Money coming in (for Assets/Expenses) or decreases (for Liabilities/Equity) | `$5,000.00` |
| **Credit** | Money going out (for Assets/Expenses) or increases (for Liabilities/Equity) | `$0.00` |

---

## Interpreting the Results

### The "Golden Rule" of Balancing
At the very bottom of the report, you will see **Total Debit** and **Total Credit**.

> [!IMPORTANT]
> **These two numbers MUST match exactly.**
>
> If Total Debit is `$1,000,000` and Total Credit is `$1,000,000`, your Trial Balance is **Balanced**.
> If they are different, something is technically wrong with the system's data integrity (very rare in modern software).

### Common Account States

| Account Type | Normal Balance | Meaning |
|--------------|----------------|---------|
| **Assets** (Cash, Inventory) | **Debit** | You have value. A Credit balance here usually means an overdraft or error. |
| **Liabilities** (Loans, Payables) | **Credit** | You owe money. |
| **Equity** (Capital) | **Credit** | The business's value. |
| **Income** (Sales) | **Credit** | You earned money. |
| **Expenses** (Rent, Salaries) | **Debit** | You spent money. |

---

## Troubleshooting

### "My Trial Balance doesn't balance!"
In JMeryar ERP, the system prevents you from posting unbalanced journal entries. However, if you see a mismatch:
1. **Check for "Draft" entries**: Sometimes unposted entries might be confusing the view (though this report typically shows posted data).
2. **Contact Support**: If Debits ≠ Credits, this is a system-level issue. Please check the **Audit Log** and contact support immediately.

### "An account is missing"
- **Zero Balance**: By default, reports might hide accounts with a $0.00 balance to keep the list clean.
- **Date Range**: Did you create the account *after* the "As of Date" you selected?

---

## Best Practices

### 📅 Monthly Review
Run this report on the last day of every month. Check for:
- **Negative Cash**: Did you spend more cash than you recorded receiving?
- **Unusual Expenses**: Is the "Miscellaneous Expense" account too high?
- **Wrong Classification**: Did post an asset purchase as an expense?

### 🔒 Lock Dates
Once you confirm the Trial Balance is correct for a period (e.g., January), ask your administrator to **Lock** the period. This prevents anyone from accidentally changing history.

---

## Related Documentation

- [Balance Sheet](balance-sheet.md) - Using these numbers to see net worth
- [Profit & Loss](profit-and-loss.md) - Analyzing your performance
- [Journal Entries](../Developers/journal_entry_flow_report.md) - How transactions are created
