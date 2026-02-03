---
title: Manufacturing Fields Reference
icon: heroicon-o-table-cells
order: 10
---

# Reference: Manufacturing Fields & Statuses

This reference provide technical details on the fields, statuses, and accounting configuration for the Manufacturing (MRP) module.

---

## 1. Manufacturing Order (MO) Statuses

Defined in `ManufacturingOrderStatus` Enum:

| Status | Meaning | Operational Impact |
| :--- | :--- | :--- |
| **Draft** | Planning stage | Can edit BOM, quantities, and dates. No stock reservation. |
| **Confirmed** | Ready to produce | Components are reserved. Production can be started. |
| **In Progress** | Manufacturing started | Components can be consumed. Work orders become active. |
| **Done** | Production complete | Finished goods received. Components consumed. Accounting entry posted. |
| **Cancelled** | Aborted | Reservations released. Work orders cancelled. |

---

## 2. Work Order Statuses

Defined in `WorkOrderStatus` Enum:

| Status | Meaning | Requirement |
| :--- | :--- | :--- |
| **Pending** | Waiting for previous step | Cannot start until preceding operations are "Done". |
| **Ready** | Prerequisites met | Work center and materials are available. |
| **In Progress** | Work active | Timer is running to track actual duration. |
| **Done** | Step finished | Operation completed. Costing recorded. |
| **Cancelled** | Operation skipped | Work no longer required. |

---

## 3. Bill of Materials (BoM) Types

| Type | Description | Best For |
| :--- | :--- | :--- |
| **Normal** | Standard assembly | Products requiring manual or machine work steps. |
| **Phantom** | Virtual grouping | Logistics grouping of components that don't need a separate MO. |
| **Kit** | Sales/Shipping set | Selling multiple products under one SKU (e.g., a Gift Set). |

---

## 4. Accounting Entries (MRP)

The system generates these entries upon Manufacturing Order completion:

| Transaction | Debit | Credit |
| :--- | :--- | :--- |
| **Production Completion** | Finished Goods Inventory | Raw Materials Inventory |
| **Work Center Overhead** | Work in Progress (WIP) | Factory Overhead (Applied) |

---

## 5. Work Center Parameters

| Parameter | Meaning | Impact |
| :--- | :--- | :--- |
| **Efficiency** | Factor of time usage | 200% = 2x faster than standard. |
| **Capacity** | Units per time | Determines scheduling constraints. |
| **OEE** | Overall Equipment Effectiveness | Metric for availability, performance, and quality. |
| **Cost per Hour** | Monetary rate | Calculates labor/overhead cost of the product. |
