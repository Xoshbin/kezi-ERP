---
title: Project Fields and Statuses
icon: heroicon-o-table-cells
order: 10
---

# Project Reference: Fields and Statuses

This reference guide details the available fields, statuses, and types used throughout the Project Management module.

---

## Project Statuses

The lifecycle of a project is defined by its status:

| Status | Code | Description |
| :--- | :--- | :--- |
| **Draft** | `draft` | Initial planning stage. No actuals (timesheets/expenses) can be logged. |
| **In Progress** | `in_progress` | Active project. Timesheets and expenses are allowed. |
| **Completed** | `completed` | Project finished. No further costs can be added. Ready for final archiving. |
| **Cancelled** | `cancelled` | Project abandoned. |

---

## Billing Types

Defines how the project generates revenue:

| Type | Code | Description |
| :--- | :--- | :--- |
| **Time & Materials** | `time_and_materials` | Billing is based on approved billable hours * hourly rate. |
| **Fixed Price** | `fixed` | Billing is based on a set contract amount, independent of hours worked. |
| **Non Billable** | `non_billable` | Internal projects or pro-bono work. No invoices can be generated. |

---

## Timesheet Statuses

The approval workflow for time entries:

| Status | Code | Description |
| :--- | :--- | :--- |
| **Draft** | `draft` | Employee is still entering time. Editable. |
| **Submitted** | `submitted` | Sent to manager for approval. Locked for employee. |
| **Approved** | `approved` | Vertified by manager. Costs are now booked to the project. Locked for everyone. |
| **Rejected** | `rejected` | Returned to employee for correction. Editable again. |

---

## Data Fields

### Project (`projects` table)

| Field | Type | Description |
| :--- | :--- | :--- |
| `name` | String | The project title. |
| `customer_id` | Relationship | The client (Partner) being billed. |
| `manager_id` | Relationship | Internal user responsible for delivery. |
| `billing_type` | Enum | See Billing Types above. |
| `hourly_rate` | Money | Default rate for billable hours (used in T&M). |
| `budget` | Money | Total planned budget amount. |
| `start_date` | Date | Project start. |
| `end_date` | Date | Projected completion. |

### Project Task (`project_tasks` table)

| Field | Type | Description |
| :--- | :--- | :--- |
| `project_id` | Relationship | Parent project. |
| `assigned_to` | Relationship | User responsible for the task. |
| `estimated_hours`| Decimal | Planned effort. |
| `progress` | Integer | 0-100 completion percentage. |
| `status` | Enum | Todo, In Progress, Review, Done. |

### Timesheet Line (`timesheet_lines` table)

| Field | Type | Description |
| :--- | :--- | :--- |
| `timesheet_id` | Relationship | Parent weekly timesheet. |
| `project_id` | Relationship | Project work was done for. |
| `task_id` | Relationship | Specific task (optional). |
| `date` | Date | Specific day of work. |
| `hours` | Decimal | Duration of work. |
| `is_billable` | Boolean | If true, this time generates revenue in T&M projects. |
