---
title: Analytic Configuration
icon: heroicon-o-chart-pie
order: 10
---

# Analytic Configuration: Plans & Accounts

This guide explains how to set up the foundation of your management accounting: **Analytic Plans** and **Analytic Accounts**.

While financial accounting (General Ledger) is legally required and standardized, analytic accounting is flexible and designed for *internal* decision-making. It answers the "Why?" and "For Whom?" questions about your income and expenses.

---

## What are Analytic Dimensions?

To use analytic accounting effectively, you need to understand two key concepts:

### 1. Analytic Plans (The Dimension)
Think of an **Analytic Plan** as a dimension or an "axis" of analysis. It groups related analytic accounts together.
*   **Examples**: "Projects", "Departments", "Vehicles", "Cost Centers".
*   **Purpose**: You can analyze your business from multiple angles at once. For example, a flight ticket cost can be tagged to a specific *Project* AND a specific *Department* simultaneously.

### 2. Analytic Accounts (The Bucket)
Think of an **Analytic Account** as the specific bucket where costs and revenues are recorded. Every analytic account belongs to a specific Plan.
*   **Examples (under "Projects" Plan)**: "Project Alpha", "Project Beta".
*   **Examples (under "Departments" Plan)**: "HR", "Sales", "IT".

---

## Where to Find It

Navigate to:
*   **Accounting → Configuration → Analytic Accounting → Analytic Plans**
*   **Accounting → Configuration → Analytic Accounting → Analytic Accounts**

---

## Setting Up Analytic Plans

Before creating accounts, you usually define your plans.

1.  Navigate to **Analytic Plans**.
2.  Click **Create Analytic Plan**.
3.  **Name**: Give it a clear name (e.g., "Projects").
4.  **Company**: Select your company.
5.  Click **Create & Create Another** if you need more (e.g., "Departments").

---

## Setting Up Analytic Accounts

Once you have your plans, you can create the specific accounts.

### Step-by-Step
1.  Navigate to **Analytic Accounts**.
2.  Click **Create Analytic Account**.
3.  Fill in the details:
    *   **Name**: Describing the account (e.g., "Project X - Website Redesign").
    *   **Plan**: Select the Plan this belongs to (e.g., "Projects").
    *   **Reference**: Optional code for easy searching (e.g., "PRJ-001").
    *   **Customer**: Optional. Link this account to a specific customer if it's a client project.
    *   **Currency**: The currency used for tracking this account's performance.
4.  Click **Create**.

---

## How It Works in Practice

When you record a **Vendor Bill** or issuing a **Customer Invoice**, you will see an **Analytic** field on the invoice lines.

### Example Scenario
You are paying for a software license costing $100.
1.  You select the **Chart of Account**: "610000 - Software Expenses". (This satisfies the legal requirement).
2.  You select the **Analytic Account**: "Marketing Dept". (This tells management *who* used it).

**The Result:**
*   **General Ledger**: Shows $100 expense in "Software Expenses".
*   **Analytic Report**: Shows $100 specific cost for "Marketing Dept".

> [!TIP]
> You can enforce analytic tags on certain expense accounts to ensure your team never forgets to tag costs!

---

## Best Practices

### 1. Keep it Simple
Don't create too many Plans. Start with the most important dimensions for your business, usually **Departments** and **Projects**.

### 2. Standard Naming
Use a consistent naming convention.
*   *Good*: "Dept - Sales", "Dept - IT"
*   *Bad*: "Sales", "The IT Guys"

### 3. Archive Old Accounts
When a project is finished, **Archive** the analytic account. This keeps your list clean but preserves the historical data for reporting.

---

## Related Documentation
*   [Analytic Report](analytic-report.md) - How to view the results.
*   [Budget Management](budget-management.md) - How to set budgets for these accounts.
