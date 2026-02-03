---
title: Understanding Production Planning
icon: heroicon-o-calendar-days
order: 60
---

# Understanding Production Planning

This guide explains how to plan your manufacturing operations effectively. Whether you manufacture to stock or to order, good planning helps you meet deadlines and optimize resource usage.

---

## What is Production Planning?

Production Planning is the process of aligning your manufacturing capacity with demand. It involves answering three key questions:
1. **What** do we need to produce? (Demand)
2. **When** do we need to produce it? (Scheduling)
3. **How many** resources (machines, people) do we need? (Capacity)

**Why does this matter?**
1. **Customer Satisfaction**: Deliver on time, every time.
2. **Efficiency**: Keep your machines running without bottlenecks.
3. **Cost Control**: Avoid rush orders and overtime costs.

> **💡 Analogy**: Think of it like planning a dinner party. You need to know who is coming (Demand), when they are arriving (Scheduling), and if your kitchen/oven is big enough to cook everything at once (Capacity).

---

## Demand Sources: Where Orders Come From

Your manufacturing orders (MOs) usually come from three places:

### 1. Sales Orders (Make to Order - MTO)
When you sell a product configured as "Replenish on Order (MTO)", confirming the Sales Order automatically generates a Manufacturing Order.
- **Best for**: Custom products, expensive items you don't stock.

### 2. Reordering Rules (Make to Stock - MTS)
The system monitors your inventory levels. If stock falls below a minimum quantity, a Manufacturing Order is triggered automatically to replenish it.
- **Best for**: High-volume items, standard products.

### 3. Manual Creation
You can always manually create a Manufacturing Order if you want to build stock ahead of a busy season.
- **Best for**: Seasonal anticipation, testing new products.

---

## Capacity Planning

Capacity planning ensures you have enough "machine time" or "labor time" to complete your orders.

### Work Centers
Each manufacturing step happens at a **Work Center** (e.g., Assembly Line 1, CNC Machine).
- **Capacity**: How many hours per day is this station available? (e.g., 8 hours/day).
- **Efficiency**: Is it running at 100% speed?

### Load vs. Capacity
The system compares the time required for your scheduled orders against the available time at the Work Center.
- **Underload**: Machines are sitting idle.
- **Overload**: You have more work than time. You might need to reschedule or add overtime.

---

## Scheduling Strategy

Scheduling determines the start and end dates for your production.

### Lead Times
The system uses **lead times** to calculate dates:
- **Manufacturing Lead Time**: How long it takes to make the product.
- **Component Lead Time**: How long it takes to get raw materials.

### Forward vs. Backward Scheduling
- **Forward Scheduling**: Starts "Now" and calculates when it will finish. Useful for "How soon can I get this?".
- **Backward Scheduling**: Starts from the "Deadline" and calculates when you must start. Useful for "When is the latest I can start to meet the promise date?".

> [!TIP]
> Always add a buffer to your lead times to account for unexpected delays like machine breakdowns or sick leave.

---

## MRP (Material Requirements Planning)

MRP is the engine that connects everything. It looks at your Manufacturing Orders and checks:
1. **Do we have the raw materials?** If not, it creates Purchase Orders.
2. **Do we have the capacity?** It warns you if you are overbooked.

**Key MRP Action**:
- **Run Scheduler**: This background process checks all rules and creates necessary orders (MOs or POs).

---

## Best Practices

### 📅 Plan Ahead
- **Review Look-ahead**: Look at your production schedule for the next week/month, not just today.
- **Freeze Period**: Try not to change the schedule for the next 2-3 days to keep the shop floor stable.

### 📊 Monitor Utilization
- **Balance the Line**: Ensure work is spread evenly across Work Centers. Avoid having one machine overloaded while others wait.
- **Preventive Maintenance**: Schedule downtime for maintenance so it doesn't happen unexpectedly during a rush order.

### ✅ Keep Data Accurate
- **Update BOMs**: Accurate times in your Bill of Materials are crucial for accurate scheduling.
- **Real-time Reporting**: Have workers record start/stop times accurately so you know your actual capacity.

---

## Troubleshooting

### Common Questions

**Q: Why is my Manufacturing Order scheduled for next month?**
A: Check your **Lead Times**. If a component has a long delivery time (e.g., 30 days), the system will push the production date back until that component arrives.

**Q: Why didn't a Sales Order create a Manufacturing Order?**
A: Check the product configuration:
1. Is the **Route** set to "Manufacture"?
2. Is "Replenish on Order (MTO)" selected?
3. Is there a valid **Bill of Materials**?

**Q: My Work Center shows 200% load. What do I do?**
A: You are overbooked!
1. **Reschedule**: Move some orders to a later date.
2. **Add Capacity**: Add a second shift or use an Alternative Work Center.

---

## Related Documentation

- [Manufacturing Concepts](manufacturing-concepts.md)
- [Manufacturing Orders](../how-to/manufacturing-orders.md)
- [Bill of Materials](../how-to/bill-of-materials.md)
- [Understanding Work Centers](understanding-work-centers.md)
- [Understanding Work Orders](understanding-work-orders.md)
