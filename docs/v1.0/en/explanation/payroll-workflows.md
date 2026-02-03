---
title: Understanding Payroll Workflows
icon: heroicon-o-information-circle
---

# Understanding Payroll Workflows

Payroll in Kezi is designed to be both accurate for employees and compliant for accounting. It bridges the gap between the Human Resources record and the General Ledger.

### What is a Payroll Run?

A **Payroll Run** is a document that calculates the earnings and deductions for a specific employee during a defined period (usually one month). It is not just a payment record; it is a calculation engine that pulls data from multiple sources:

1. **Employment Contract**: Fetches the base salary and recurring allowances.
2. **Leave Management**: Identifies unpaid leave taken during the period to calculate deductions.
3. **Manual Adjustments**: Allows for one-time bonuses, overtime, or specific penalties.

---

### Integrity and Accounting

When a payroll is processed, it automatically generates a **Journal Entry**. This ensures that the company's financial reports accurately reflect its liabilities and expenses without manual data entry.

#### The Accrual Process
In Kezi, payroll follows the accrual principle:
*   **Approval**: Records the expense and the liability (what you owe the employee).
*   **Payment**: Reduces the liability and records the cash outflow from the bank.

This separation allows you to "close" the month's books even if the actual bank transfer happens a few days later.

---

### Key Concepts

#### Base Salary vs. Net Pay
*   **Base Salary**: The fixed amount defined in the contract.
*   **Allowances**: Additional recurring payments (Housing, Transport).
*   **Deductions**: Reductions due to leave, taxes, or other penalties.
*   **Net Pay**: The final amount that will be transferred to the employee's bank account.

#### Batch Processing
For companies with many employees, Kezi allows you to review and process payrolls in batches, ensuring consistency across departments.

---

### Related Resources
- [How-to: Process Payroll](../how-to/process-payroll.md)
- [Reference: Payroll Statuses and Accounting](../reference/payroll-statuses-and-accounting.md)
- [Tutorial: Processing Your First Payroll](../tutorials/processing-your-first-payroll.md)
