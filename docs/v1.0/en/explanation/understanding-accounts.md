# Understanding Accounts (Chart of Accounts)

The Chart of Accounts is the backbone of your accounting system. It's a complete listing of every account in your general ledger, organized by type and purpose.

## Overview

An Account (also called a ledger account or GL account) is where financial transactions are recorded. Each account tracks a specific type of asset, liability, equity, income, or expense.

## Key Concepts

### Account Types

Kezi uses five fundamental account types:

*   **Asset**: Things your business owns (Cash, Inventory, Equipment)
*   **Liability**: What your business owes (Loans, Accounts Payable)
*   **Equity**: Owner's stake in the business (Capital, Retained Earnings)
*   **Income**: Revenue from business activities (Sales, Service Revenue)
*   **Expense**: Costs of running the business (Rent, Salaries, Utilities)

### Account Code

Each account has a unique code that determines its position in the chart. Typical conventions:
*   1xxx - Assets
*   2xxx - Liabilities
*   3xxx - Equity
*   4xxx - Income
*   5xxx - Expenses

### Account Groups

Accounts are organized into [Account Groups](account-groups.md) for reporting purposes. This allows you to see summarized totals on financial statements.

### Reconciliation

Some accounts (like bank accounts) can be marked for reconciliation, enabling you to match your records against external statements.

## Creating an Account

1.  Navigate to **Accounting > Configuration > Chart of Accounts**.
2.  Click **Create Account**.
3.  Fill in the details:
    *   **Code**: Unique identifier (e.g., `101001`)
    *   **Name**: Descriptive name (e.g., "Cash on Hand")
    *   **Type**: Select the appropriate account type
    *   **Account Group**: Choose or create a group for reporting
    *   **Currency**: If this account tracks a specific currency
    *   **Reconcile**: Enable for accounts that need statement matching

## Best Practices

1.  **Plan Your Structure**: Design your chart of accounts before entering transactions
2.  **Use Consistent Coding**: Follow a logical numbering pattern
3.  **Keep It Simple**: Don't create more accounts than necessary
4.  **Document Purpose**: Use descriptions to clarify each account's purpose
5.  **Review Regularly**: Periodically clean up unused accounts

## Related Topics

*   [Account Groups](account-groups.md)
*   [Journal Entries](journal-entries.md)
*   [Opening Balances](opening-balances.md)
