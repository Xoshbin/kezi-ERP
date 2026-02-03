---
title: Understanding Employee Records
icon: heroicon-o-information-circle
---

# Understanding Employee Records

In Kezi, the **Employee** module serves as the central hub for your company's human capital. It is more than just a list of names; it is a dynamic database that connects people to their roles, contracts, payroll, and performance.

### The Role of an Employee Record

An employee record is the digital identity of a staff member within the ERP. It acts as the "source of truth" for several other modules:

1. **HR & Contracts**: Links a person to their legal and financial terms of employment.
2. **Payroll**: Provides the identity for salary slips and banking details for payments.
3. **Leave & Attendance**: Tracks time-off entitlements and presence based on the individual.
4. **Security & Access**: Can be linked to a **System User** to allow the employee to log in and use self-service features.

---

### Key Concepts

#### Centralized Identity
By keeping personal info, job history, and bank details in one place, you avoid data silos. If an employee changes their bank account, updating it in the employee record ensures the next payroll run uses the correct information automatically.

#### Reporting Hierarchy
Employee records allow you to define **Managers**. This creates a reporting structure that the system uses for approval workflows. For example, when an employee requests leave, the system automatically knows to notify their assigned manager.

#### Privacy and Data Sensitivity
Employee records contain sensitive personal data (National IDs, salaries, home addresses). Kezi implements strict access controls. Only users with HR Manager roles can typically view full details, while regular managers might only see contact and job-related info for their team.

---

### Employee Status Lifecycle

An employee's status determines their eligibility for various system processes:

* **Active**: The employee is currently employed. They appear in payroll runs, can request leave, and are included in headcount reports.
* **Inactive / Terminated**: The employee is no longer with the company. While their record remains in the system for historical reporting and audit purposes, they are excluded from current payroll runs and cannot access the system.

> [!TIP]
> **Never delete an employee** who has historical records (like past payments or leave). Instead, use the "Terminated" status to maintain data integrity.

---

### Related Resources
- [How-to: Manage Employees](../how-to/manage-employees.md)
- [Reference: Employee Fields](../reference/employee-fields-and-statuses.md)
- [Tutorial: Onboarding Your First Employee](../tutorials/onboarding-your-first-employee.md)
