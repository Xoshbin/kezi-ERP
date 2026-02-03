---
title: Understanding Warehouse Transfers
icon: heroicon-o-arrow-path
order: 13
---

# Understanding Warehouse Transfers

Warehouse transfers are specialized stock movements that track goods moving between different warehouse locations. In Kezi, these transfers follow a specialized two-step workflow to ensure complete control and accountability.

---

## Why Use Warehouse Transfers?

While simple internal movements handle shifting stock within the same facility, inter-warehouse transfers are designed for moving goods across different physical locations.

- **Accountability**: Separate responsibilities for shipping from the source and receiving at the destination.
- **Visibility**: Monitor stock while it is actually on the truck or in transit.
- **Control**: Verify quantities at both ends of the journey.
- **Audit Trail**: Complete record of who shipped, when they shipped, and who received the goods.

---

## The Two-Step Workflow

Unlike a one-step movement where stock disappears from location A and appears in location B instantly, warehouse transfers use a **ship → receive** model.

### 1. Ship (Source to Transit)
When the sending warehouse ships the goods:
- Stock is removed from the **Source Warehouse**.
- Stock is added to a **Virtual Transit Location**.
- Physical stock is officially "on the road."

### 2. Receive (Transit to Destination)
When the receiving warehouse accepts the goods:
- Stock is removed from the **Virtual Transit Location**.
- Stock is added to the **Destination Warehouse**.
- Physical stock is officially "in stock" at the new location.

---

## Simple vs. Two-Step Transfers

Choosing the right method depends on the nature of the movement:

### Simple Internal Transfers
- **When**: Moving stock within the same physical warehouse (e.g., from Zone A to Zone B).
- **Characteristic**: Happens instantly.
- **Requirement**: Usually handled by a single person.

### Inter-Warehouse Transfers
- **When**: Moving stock between separate warehouses or cities.
- **Characteristic**: Goods spend time "in transit" where they aren't physically at either warehouse.
- **Requirement**: Requires two-step verification (Shipping and Receiving).

---

## Related Documentation
- [How-to: Managing Warehouse Transfers](../how-to/inter-warehouse-transfers.md)
- [Reference: Warehouse Transfer Details](../reference/warehouse-transfers.md)
- [Explanation: Inventory Architecture](inventory-architecture.md)
