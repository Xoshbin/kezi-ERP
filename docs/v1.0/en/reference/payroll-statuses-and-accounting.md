---
title: Payroll Statuses and Accounting
icon: heroicon-o-table-cells
---

# Payroll Reference Guide

This guide provides technical details on payroll workflow stages and the resulting accounting entries.

### Workflow Statuses

Each payroll record moves through a series of stages to ensure proper review and authorization.

| Status | Meaning | System Action |
|:---|:---|:---|
| **Draft** | Calculation in progress. | Editable; no accounting impact. |
| **Processed** | Approved by a manager. | Locked; generates **Journal Entry** (Dr Expense / Cr Payable). |
| **Paid** | Payment has been issued. | Links to a **Payment** record; Debit to Liability. |
| **Closed** | Finalized and historical. | No further changes possible. |

---

### Accounting Logic

When a payroll is moved from **Draft** to **Processed**, the system generates a Journal Entry. Below is a standard example:

**Scenario**: Monthly salary of $1,000.

| Account | Debit | Credit | Note |
|:---|:---|:---|:---|
| 601001 - Salary Expense | $1,000.00 | | Records the cost to the company. |
| 201001 - Salaries Payable | | $1,000.00 | Records the liability to the employee. |

**When the payment is made:**

| Account | Debit | Credit | Note |
|:---|:---|:---|:---|
| 201001 - Salaries Payable | $1,000.00 | | Clears the liability. |
| 101001 - Bank/Cash | | $1,000.00 | Records the money leaving the bank. |

---

### Data Sources

The following fields are automatically populated from other modules:

*   **Employee Name/ID**: From the Employee record.
*   **Base Salary**: From the Active Employment Contract.
*   **Bank Account**: From the Employee financial tab.
*   **Attendance Deduction**: Calculated based on [Leave Management](../how-to/manage-leave.md) records tagged as "Unpaid".

---

### Related Resources
- [How-to: Process Payroll](../how-to/process-payroll.md)
- [Explanation: Payroll Workflows](../explanation/payroll-workflows.md)
