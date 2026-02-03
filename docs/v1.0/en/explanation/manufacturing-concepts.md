---
title: Manufacturing Concepts
icon: heroicon-o-academic-cap
order: 10
---

# Manufacturing Concepts: How It All Fits Together

Understanding the core concepts of the Manufacturing (MRP) module is essential for optimizing your production and maintaining accurate inventory and financial records.

## The Manufacturing Ecosystem

The manufacturing module in Kezi ERP is designed to track the transformation of raw materials into finished goods. It connects inventory, planning, and accounting in a seamless flow.

### 1. The Bill of Materials (BoM)
The **BoM** is the foundation. It’s not just a list of materials; it defines the structure of your product.
- **Normal BoM**: Standard hierarchical recipe.
- **Phantom BoM**: Used to group components logically without creating a separate physical step.
- **Kit BoM**: Used for selling sets of products under a single name.

### 2. Work Centers & Operations
**Work Centers** represent the physical constraints of your factory (machines, stations, assembly lines). **Operations** are the specific tasks performed at these centers (cutting, welding, assembly).
- **Capacity**: Defines how much work a center can handle.
- **Efficiency**: Adjusts scheduling based on how fast the center realistically operates.
- **OEE**: A metric that tells you how well your machines are being utilized.

### 3. Manufacturing Orders (MO) vs. Work Orders (WO)
- **Manufacturing Order (MO)**: The "Project". It says "Make 100 Chairs".
- **Work Orders (WO)**: The "Steps". They say "Step 1: Cut Wood at Saw Station", "Step 2: Assemble at Table 1".

You cannot finish the MO until all required WOs are completed.

---

## The MRP Lifecycle

1.  **Demand Generation**: A Sales Order or a Reordering Rule identifies a need for stock.
2.  **Planning**: A Manufacturing Order is created and confirmed, reserving materials.
3.  **Execution**: Operators start Work Orders at their Work Centers.
4.  **Completion**: Finished goods are produced, materials are consumed, and accounting entries are posted automatically.

---

## Accounting & Costing

Kezi ERP uses **BOM Costing** to value your finished products.
- **Material Cost**: The weighted average cost (AVCO) or FIFO cost of all consumed components.
- **Labor & Overhead**: Calculated based on the hourly rate of the Work Centers and the actual time spent on Work Orders.

When an MO is completed, the system:
1.  **Credits** Raw Materials Inventory.
2.  **Debits** Finished Goods Inventory.
3.  Adjusts **Work in Progress (WIP)** if the operation spans multiple days.

---

## Best Practices for MRP Success

-   **Keep BoMs Accurate**: Inaccurate quantities or missing components will lead to incorrect inventory levels and flawed costing.
-   **Monitor OEE**: Use Work Center reports to identify bottlenecks. Is a specific machine constantly overloaded?
-   **Be Diligent with Time Tracking**: Encourage operators to use the "Start" and "Done" buttons on Work Orders. This provides the data needed for accurate labor costing.

## Related Guides
- [Understanding Work Centers](understanding-work-centers.md)
- [Understanding Work Orders](understanding-work-orders.md)
- [Manufacturing Fields Reference](../reference/manufacturing-fields.md)
