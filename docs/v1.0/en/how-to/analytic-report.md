---
title: Analytic Report
icon: heroicon-o-chart-pie
order: 11
---

# Analytic Report: Cost & Profitability Analysis

The **Analytic Report** (or Analytic Ledger) allows you to analyze your financial performance by **dimension** (e.g., project, department, cost center) rather than just by the nature of the expense (e.g., salary, rent). This is the core of **management accounting**.

While the [Profit & Loss](profit-loss-report.md) tells you *what* you spent money on, the Analytic Report tells you *why* or *for whom* you spent it.

---

## What is an Analytic Account?

An **Analytic Account** is a secondary "tag" or axis for tracking costs and revenues. It runs parallel to your General Ledger but doesn't affect your legal financial statements.

*   **General Ledger**: "We spent $1,000 on Flight Tickets." (Legal Requirement)
*   **Analytic Account**: "This $1,000 belongs to Project X." (Management Insight)

### Key Concepts

*   **Analytic Account**: A specific cost center or revenue bucket (e.g., "HR Department", "Project Alpha").
*   **Analytic Plan**: A grouping of analytic accounts (e.g., "Departments", "Projects"). This allows you to track multiple dimensions simultaneously.
    *   *Example*: A single expense invoice can be tagged to "Department: Sales" AND "Project: Q4 Campaign".

---

## Where to Find It

Navigate to: **Accounting → Reports → Analytic Report**

---

## How to Use the Report

### 1. Filter by Plan or Account
To get meaningful data, you usually want to filter:
*   **Analytic Plan**: Select "Projects" to see profitability for all projects.
*   **Analytic Accounts**: Select specific accounts (e.g., "Project Alpha", "Project Beta") to compare them.

### 2. Set the Date Range
*   **Start Date**: The beginning of the period you want to analyze.
*   **End Date**: The end of the period.

### 3. Generate Analysis
Click **Generate Report** to view the breakdown.

---

## Understanding the Columns

The report typically displays:

| Column | Description |
| :--- | :--- |
| **Analytic Account** | The name of the cost center or project (e.g., "Project Alpha"). |
| **Debit** | Costs and expenses allocated to this account. |
| **Credit** | Revenues or budget allocations credited to this account. |
| **Balance** | `Credit - Debit`. A positive balance usually indicates profit (more revenue than cost), while a negative balance indicates a net cost. |

> [!NOTE]
> Unlike the General Ledger where Debit is usually positive for assets/expenses, in Analytic Reports, we often look at the **Balance** to see the net financial impact.

---

## Common Use Cases

### 1. Project Profitability
*   **Goal**: Knowing if "Project A" made money.
*   **Setup**: Tag all Client Invoices (revenue) and Vendor Bills (expenses) related to Project A with the "Project A" analytic account.
*   **Result**: The Analytic Report Balance shows appropriate Net Profit for strictly that project.

### 2. Departmental Costing
*   **Goal**: Tracking how much the "IT Department" costs the company.
*   **Setup**: Expenses (Laptops, Salaries) are tagged with "IT Department".
*   **Result**: The report shows total running costs for IT.

### 3. Grant Budgeting (NGOs)
*   **Goal**: Ensuring you don't overspend a specific grant.
*   **Setup**: Expenses are tagged to "Grant #123".
*   **Result**: Monitor the total spend against the grant revenue.

---

## Drill Down
You can often click on an **Analytic Account** name in the report (if supported) or use the **Analytic Items** view to see the individual transactions that make up the balance.

---

## Best Practices
*   **Mandatory Tags**: For critical projects, make Analytic Accounts mandatory on Purchase Orders and Bills to ensure no cost is missed.
*   **Consistency**: Use Analytic Plans to keep "Departments" separate from "Projects" to avoid reporting confusion.

---

## Related Documentation
*   [Profit & Loss](profit-loss-report.md) - General financial performance.
*   [General Ledger](general-ledger-report.md) - Default accounting records.
