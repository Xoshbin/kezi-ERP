---
title: Understanding Multi-Tenancy
icon: heroicon-o-building-library
---

# Understanding Multi-Tenancy

Kezi ERP is built with a multi-tenant architecture, allowing you to manage multiple distinct companies or business units within a single installation.

## What is a Tenant?

In Kezi, a **Tenant** usually represents a legal entity or a Company. Each tenant has its own isolated data, inclusive of:

-   Chart of Accounts
-   Customers and Vendors
-   Transactions (invoices, bills, payments)
-   Inventory and Warehouses
-   Employees and Payroll
-   Settings and Configuration

## Data Isolation

Data is rigorously isolated between tenants. A user logged into **Company A** cannot see or access the data of **Company B**, unless they explicitly switch their context to Company B (and have permissions to do so).

## User Access and Tenants

Users can belong to multiple tenants.

-   **Global Users**: Can be granted access to multiple companies.
-   **Tenant-Specific Roles**: A user might be an "Administrator" in Company A but only a "Viewer" in Company B.

## Shared Resources

While most transactional data is isolated, some system-level resources might be shared purely for configuration purposes, depending on the hosting setup (e.g., subscription plans, global system settings), but in a standard deployment, companies operate largely independently.

## Switching Companies

Users with access to multiple companies can switch between them using the **Tenant Switcher** usually located in the top navigation bar or user menu.

## See Also

- [Setting Up Your Company](../tutorials/setting-up-your-company.md)
- [Manage Users](../how-to/manage-users.md)
