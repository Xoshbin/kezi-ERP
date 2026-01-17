---
title: Tax Report
icon: heroicon-o-document-text
order: 10
---

# Tax Report: Statutory Compliance Made Easy

This guide explains how to use the **Tax Report** to summarize taxes collected on sales and taxes paid on purchases. This report is essential for filing your regulatory tax returns (such as VAT) accurately and on time.

---

## What is the Tax Report?

The Tax Report calculates your **Net Tax Statement** for a specific period. It answers the critical question: *"How much tax do I owe the government, or how much do they owe me?"*

It works on this simple formula:

> **Output Tax (Sales) - Input Tax (Purchases) = Net Tax Payable (or Refundable)**

**Key Concepts:**
*   **Output Tax**: The tax you collected from customers when you sold goods or services. You owe this to the tax authority.
*   **Input Tax**: The tax you paid to vendors when you bought goods or services. You can usually claim this back.
*   **Net Tax**: The difference between the two. If Output > Input, you pay the difference. If Input > Output, you may get a refund.

---

## Where to Find It

Navigate to: **Accounting → Reports → Tax Report**

> **💡 Tip**: You can also access this report from the "Reporting" dashboard if you have it pinned.

---

## How to Use the Report

### 1. Set Your Date Range
Tax returns are usually filed for a specific period (monthly, quarterly, or annually).
*   **Start Date**: The beginning of the tax period (e.g., Jan 1).
*   **End Date**: The end of the tax period (e.g., Mar 31).

### 2. Generate the Report
Click the **Generate Report** button (<heroicon-o-play class="w-4 h-4 inline"/>). The system will calculate totals from all posted transactions.

---

## Understanding the Sections

The report is divided into two main sections:

### 1. Output Tax (Sales)
This section lists all taxes collected from your **Customer Invoices** and **Credit Notes**.
*   **Tax Group**: Taxes are grouped by type (e.g., "VAT Standard 15%", "VAT Zero Rated").
*   **Net Amount**: The value of goods/services sold excluding tax.
*   **Tax Amount**: The actual tax collected on those sales.

### 2. Input Tax (Purchases)
This section lists all taxes paid on your **Vendor Bills** and **Debit Notes**.
*   **Tax Group**: Taxes are grouped by type (e.g., "VAT Standard 15%", "Import VAT").
*   **Net Amount**: The value of goods/services purchased excluding tax.
*   **Tax Amount**: The tax paid on those purchases.

### 3. Net Tax Payable
The final line shows the total calculation:
*   **Positive Amount**: You owe this amount to the tax authority (**Payable**).
*   **Negative Amount**: The tax authority owes you this amount (**Refundable**).

---

## Common Scenarios

### Scenario A: Filing a Quarterly VAT Return
**Situation**: You need to file your VAT return for Q1 (January - March).
**Steps**:
1.  Go to **Tax Report**.
2.  Set date range: `Jan 1` to `Mar 31`.
3.  Click **Generate**.
4.  Use the **Total Output Tax** figure for "Total Sales VAT" box in your summary.
5.  Use the **Total Input Tax** figure for "Total Purchase VAT" box in your summary.
6.  The **Net Tax Payable** should match the amount you pay via bank transfer.

### Scenario B: Verifying Tax Accounts
**Situation**: You want to ensure your Balance Sheet "Tax Payable" liability matches this report.
**Steps**:
1.  Run the **Tax Report** for the period.
2.  Note the **Net Tax Payable**.
3.  Open the **Balance Sheet** for the same end date.
4.  Check the **Current Liabilities** section for your Tax/VAT Control account.
5.  The numbers should generally match (unless there are manual journal entries or payments already made).

---

## Best Practices

### 🔒 Post All Transactions
Ensure all invoices and bills for the period are **Posted**. Draft transactions are NOT included in the Tax Report.

### 📅 Watch the Dates
Be careful with the transaction date vs. the accounting date. The report uses the **Accounting Date**. If you date a bill March 31st but it relates to April, verify which period it falls into.

### 🔍 Audit the Numbers
If a tax amount looks wrong (e.g., too high):
1.  Go to the **General Ledger**.
2.  Filter by the Tax Account (e.g., "VAT Output").
3.  Look for unusual entries or manual journals that shouldn't be there.

---

## Related Documentation

*   [Profit & Loss](profit-loss-report.md) - Your revenue and expenses.
*   [Balance Sheet](balance-sheet-report.md) - Your assets and liabilities.
*   [General Ledger](general-ledger-report.md) - Detailed transaction history.
