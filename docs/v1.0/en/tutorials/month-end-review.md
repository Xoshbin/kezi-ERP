---
title: Conducting a Month-End Review
icon: heroicon-o-calendar-days
---

# Conducting a Month-End Review

Closing your books at the end of each month is a critical practice to ensure your financial data is accurate and ready for tax reporting or business analysis. This tutorial walks you through a standard month-end review process in Kezi.

## Prerequisites
Before starting your review, ensure that:
- All Invoices and Vendor Bills for the month have been recorded and validated.
- All Payments have been recorded.
- **Bank Reconciliation** is completed for all bank accounts.

---

## Step 1: Check the Trial Balance
The Trial Balance is your first stop to ensure the technical integrity of your books.

1.  Navigate to **Accounting > Reports > Trial Balance**.
2.  Set the **As of Date** to the last day of the month (e.g., `2024-01-31`).
3.  Click **Generate Report**.
4.  **The Check**: Scroll to the bottom. **Total Debit** must equal **Total Credit**. 
5.  **Scan for Oddities**: Look for accounts with balances that seem impossible (e.g., negative "Office Supplies" or a credit balance in "Cash").

---

## Step 2: Analyze the Profit and Loss
Now that you know the accounts are balanced, see how the business performed during the month.

1.  Navigate to **Accounting > Reports > Profit and Loss**.
2.  Set the **Start Date** and **End Date** for the month.
3.  Click **Generate Report**.
4.  **The Review**:
    - **Revenue**: Does it match your sales expectations?
    - **Gross Profit**: Is your margin healthy?
    - **Expenses**: Are there any unexpected spikes? Use the **General Ledger** to drill down into any suspicious expense accounts.

---

## Step 3: Review the Balance Sheet
Verify your financial position at the end of the month.

1.  Navigate to **Accounting > Reports > Balance Sheet**.
2.  Set the **As of Date** to the last day of the month.
3.  Click **Generate Report**.
4.  **The Review**:
    - **Cash Balance**: Compare this to your actual bank statements.
    - **Accounts Receivable**: Look at the **Aged Receivables** report to see who still owes you money.
    - **Accounts Payable**: Look at the **Aged Payables** report to see what bills are coming due.

---

## Step 4: Lock the Period
Once you are satisfied that the numbers are correct, you should "lock" the month to prevent accidental changes.

1.  Navigate to **Accounting > Configuration > Lock Dates**. (Note: This may require administrator permissions).
2.  Set the **Journal Entries Lock Date** to the last day of the month you just reviewed.
3.  Save the settings.

> [!SUCCESS]
> Congratulations! Your month is officially closed. By following this process every month, you ensure that your end-of-year tax preparation will be stress-free.

---

## Related Guides
- [Generating Financial Reports](../how-to/generating-financial-reports.md)
- [Financial Reporting Concepts](../explanation/financial-reporting-concepts.md)
- [Financial Report Terms](../reference/financial-report-terms.md)
