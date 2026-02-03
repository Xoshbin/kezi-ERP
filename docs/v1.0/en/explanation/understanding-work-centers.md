---
title: Understanding Work Centers
icon: heroicon-o-building-storefront
order: 10
---

# Understanding Work Centers: The Heart of Manufacturing

This guide explains what Work Centers are and how to manage them effectively. Work Centers are the physical locations or machines where your manufacturing operations happen.

---

## What is a Work Center?

Think of a Work Center as a specific station on an assembly line. It could be:
- A specific machine (e.g., "Drill Press #1")
- A manual assembly table (e.g., "Packaging Station")
- A specialized area (e.g., "Painting Booth")

**Why are Work Centers important?**
1. **Scheduling**: They help you know if you have the capacity to build what you need.
2. **Costing**: They allow you to track the cost of labor and machine time for each product.
3. **Efficiency**: They help you measure how well your production line is performing.

---

## How it Works

### 🏭 Visualizing the Production Line

Imagine a simple furniture factory. Your Work Centers might look like this:

```
[ Sawing Station ] ──▶ [ Assembly Table ] ──▶ [ Painting Booth ]
     (Machine)             (Manual)              (Resource)
```

Each product moves through these stations. If the "Sawing Station" is busy, production stops. Work Centers help you prevent these bottlenecks.

---

## Key Concepts

### 1. Capacity Planning ⚡

Capacity is how much work a center can handle. It is calculated based on:
- **Working Hours**: How many hours per day the center is active.
- **Efficiency**: How fast the center works compared to the standard.

> [!NOTE]
> If a machine is rated at **100% efficiency** and works **8 hours**, it has 8 hours of capacity. If it runs at **200% efficiency**, it can do 16 hours' worth of work in that same 8-hour shift!

### 2. Costing 💰

Every minute spent in a Work Center costs money. This includes:
- **Cost per Hour**: The rate you charge for using this center (e.g., electricity, operator wages).
- **Setup Cost**: One-time cost to prepare the center (e.g., cleaning the painting booth).

These costs are automatically added to the final cost of your manufactured product.

### 3. Performance (OEE) 📊

**Overall Equipment Effectiveness (OEE)** is a way to measure how effectively a Work Center is being used. It considers:
- **Availability**: Is the machine running or broken down?
- **Performance**: Is it running at full speed?
- **Quality**: Are the products coming out good or defective?

### 4. Alternative Work Centers 🔄

What happens if your primary Work Center is busy or broken? You can define an **Alternative Work Center**.

- **Scenario**: "Drill Press A" is overloaded.
- **Solution**: The system can automatically schedule the work to "Drill Press B" to keep production moving.

---

## Troubleshooting

### Why is my Work Center overloaded?

If your Work Center shows a red "Overloaded" status, check:
1. **Planned Orders**: Do you have too many manufacturing orders scheduled for today?
2. **Capacity Settings**: Is the standard capacity set correctly? (e.g., did you forget to add the night shift?)
3. **Efficiency**: Is the efficiency set too low, making the system think it takes longer to do the work?

> [!TIP]
> Use the **Work Center Load Report** to see a graph of planned hours vs. available capacity.

---

## Related Documentation

- [Manufacturing Concepts](manufacturing-concepts.md)
- [Understanding Work Orders](understanding-work-orders.md)
- [Manufacturing Orders](../how-to/manufacturing-orders.md)
