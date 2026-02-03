---
title: Leave Types and Statuses
icon: heroicon-o-table-cells
---

# Leave Reference Guide

This guide provides technical specifications for leave types and the status lifecycle of a leave request.

### Common Leave Types

| Type | Default Paid? | Requires Approval? | Usage Note |
|:---|:---:|:---:|:---|
| **Annual Leave** | Yes | Yes | Standard vacation time. |
| **Sick Leave** | Yes | Yes | Often requires a medical certificate. |
| **Paternity/Maternity** | Yes | Yes | Long-term family leave. |
| **Unpaid Leave** | No | Yes | Affects salary calculation. |
| **Emergency Leave** | Yes | Yes | For urgent, unplanned personal matters. |

---

### Request Statuses

Each leave request moves through a controlled workflow to ensure management oversight.

| Status | Meaning | System Impact |
|:---|:---|:---|
| `Pending` | Submitted, awaiting review. | Days are "reserved" but not yet deducted from balance. |
| `Approved` | Manager has granted the leave. | Days are officially deducted from the balance. |
| `Rejected` | Manager has declined the request. | Reserved days are released back to the balance. |
| `Cancelled` | Employee or HR cancelled the request. | If previously approved, days are restored to the balance. |

---

### Attendance Statuses

| Status | Meaning |
|:---|:---|
| `Present` | Employee clocked in and is onsite. |
| `Absent` | Expected to work but no clock-in recorded. |
| `On Leave` | Not at work due to an approved leave request. |

---

### Validation Rules

- **Negative Balances**: By default, the system prevents submitting a request if the remaining balance is less than the requested days.
- **Overlap Prevention**: The system will error if you try to submit two leave requests for the same date.
- **Date Range**: The "End Date" cannot be before the "Start Date".

---

### Related Resources
- [How-to: Manage Leave](../how-to/manage-leave.md)
- [Explanation: Leave and Attendance Concepts](../explanation/leave-and-attendance-concepts.md)
