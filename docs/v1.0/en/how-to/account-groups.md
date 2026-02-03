# Account Groups

Account Groups are the structural backbone of your financial reporting in Kezi. They allow you to aggregate individual General Ledger accounts into meaningful categories for reporting purposes, such as the Balance Sheet and Profit & Loss statement.

## Overview

Instead of listing every single account on a report (which would be overwhelming), Account Groups let you organize them into a hierarchy. For example, you can group "Cash on Hand", "Bank Accounts", and "Petty Cash" under a single group called "Cash and Equivalents". This provides a clean, summarized view of your financial health.

## Key Concepts

### Hierarchy (Parent/Child)
Account Groups function in a tree structure.
- **Parent Group**: A broad category (e.g., "Current Assets").
- **Child Group**: A more specific category nestled within a parent (e.g., "Cash and Equivalents" inside "Current Assets").

This hierarchy determines how totals roll up in your reports. The balance of "Cash and Equivalents" is added to the balance of "Current Assets".

### Code Prefix
Kezi allows you to define a range of account codes for each group (e.g., 1000 - 1099). When you create a new General Ledger account and assign a code within this range, the system can automatically suggest the correct Account Group, helping keep your chart of accounts organized.

### Account Types
Each group is associated with a specific Account Type, which dictates where it appears:
*   **Assets & Liabilities**: Balance Sheet
*   **Income & Expenses**: Profit & Loss Statement
*   **Equity**: Balance Sheet/Statement of Changes in Equity

## Creating an Account Group

1.  Navigate to **Accounting > Configuration > Account Groups**.
2.  Click **Create Account Group**.
3.  Fill in the details:
    *   **Name**: A descriptive name for the group (e.g., "Liquid Assets").
    *   **Code Prefix Start / End**: The range of account codes this group covers (e.g., Start: `101000`, End: `101999`).
    *   **Parent Group**: Select an existing group if this is a sub-group (e.g., select "Current Assets" as the parent).
    *   **Level**: This is often automatically calculated based on the parent, but defines the indentation level in reports.

## Impact on Reports

The structure you define here is directly reflected in your financial reports.
*   **Balance Sheet**: Changing the parent of "Vehicle Assets" from "Fixed Assets" to "Current Assets" will move that entire section and its total to the Current Assets part of the Balance Sheet.
*   **Profit & Loss**: Grouping expense accounts (e.g., "Travel", "Meals", "Hotels") under a "Travel Expenses" group helps you analyze complete spending categories at a glance.
