---
title: Employee Fields and Statuses
icon: heroicon-o-table-cells
---

# Employee Reference Guide

This reference guide provides technical details about the fields and status values found within the Employee module.

### Core Fields

The employee form is divided into sections to help organize complex data.

| Field Name | Description | Key Requirement |
|:---|:---|:---|
| **Full Name** | The legal name of the employee. | Required |
| **Email** | Official company email address. | Unique |
| **Phone** | Primary contact number. | Recommended |
| **Department** | The organizational unit they belong to. | Required |
| **Position** | Their official job title. | Required |
| **Manager** | The person they report to. | Used for approvals |
| **Join Date** | The first official day of work. | Required |
| **Status** | The current employment state. | Defaults to Active |

---

### Personal & Financial Details

| Field Name | Description |
|:---|:---|
| **Date of Birth** | Used for HR compliance and age-related reporting. |
| **ID / Passport No.** | Legal identification for contracts and government filings. |
| **Bank Account** | The IBAN or account number for salary transfers. |
| **Emergency Contact** | Name and phone of a person to call in emergencies. |

---

### Status Definitions

The `Status` field on an employee record controls their visibility and participation in system workflows.

| Status | Meaning | System Impact |
|:---|:---|:---|
| `Active` | Employed and currently working. | Included in Payroll, Leave, and Attendance. |
| `Inactive` | Temporary absence or pre-onboarding. | Typically excluded from active lists. |
| `Terminated` | No longer employed. | Retained for history; excluded from all current processing. |

---

### Attachments

Common document types stored in the employee record:

1. **Signed Contracts**: Digital copies of employment agreements.
2. **Identification**: Scans of IDs, Passports, or Residency permits.
3. **Education**: Degree certificates and training diplomas.
4. **Photos**: Official headshots for ID cards/profiles.

---

### Related Resources
- [How-to: Manage Employees](../how-to/manage-employees.md)
- [Explanation: Employee Records](../explanation/employee-records.md)
