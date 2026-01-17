---
title: Quality Points
icon: heroicon-o-cog-6-tooth
order: 3
---

# Quality Points: Automating Your Quality Checks

This guide explains how to use **Quality Points** to set up automatic quality inspections in your workflow. Whether you're receiving goods, manufacturing products, or shipping to customers, Quality Points ensure no step stays unchecked.

---

## What is a Quality Point?

Think of a **Quality Point** as a "security checkpoint" or a "rule" that you set up in advance. It tells the system: 
*"When [Operation] happens for [Product], trigger a [Quality Check]."*

Instead of manually creating a quality check every time, Quality Points do it for you automatically.

**Why use them?**
1.  **Consistency**: Ensure standard procedures are followed every time.
2.  **Automation**: No need to remember to create checks; the system does it.
3.  **Control**: Stop bad products from moving to the next stage (if configured as blocking).

---

## Where to Find It

Navigate to: **Settings → Quality → Quality Points**

You'll see a list of all active quality rules currently running in your system.

---

## Creating a Quality Point

Let's walk through creating a new Quality Point rule.

### Step 1: Start Fresh

Click the **New Quality Point** button. You'll see a form with these settings:

| Field | Description | Example |
|-------|-------------|---------|
| **Name** | A clear name for this rule | "Incoming Cabling Inspection" |
| **Trigger Operation** | When should this check happen? | Goods Receipt, Customer Delivery |
| **Trigger Frequency** | How often to check | All Operations, Randomly, Periodically |
| **Product** | The specific product to check (leave empty for all) | "Ethernet Cable Cat6" |
| **Inspection Template** | The checklist to use | "Cable Resistance Check" |
| **Is Blocking?** | Should this stop the process if failed? | Yes (Process blocked until passed) |

### Step 2: Configure the Trigger

**Trigger Operations** define *when* the check appears:
*   **Goods Receipt**: When you receive items from a vendor.
*   **Internal Transfer**: When moving stock between your own warehouses.
*   **Manufacturing Output**: When finishing a production order.
*   **Customer Delivery**: Before shipping to a client.

**Trigger Frequencies** define *how often*:
*   **Per Operation**: Triggers on every single movement (100% inspection).
*   **Per Product**: Triggers once per product type in a batch.
*   **Per Quantity**: Triggers based on quantity thresholds (e.g., "Check if quantity > 50").

### Step 3: Define Behavior

*   **Quantity Threshold**: If you selected "Per Quantity", enter the number here.
*   **Is Blocking**: 
    *   **Enabled (On)**: The transfer/operation CANNOT be validated until the quality check is "Passed".
    *   **Disabled (Off)**: The check is generated, but the user can bypass it or proceed even if it fails (useful for advisory checks).

---

## Common Scenarios

### Scenario 1: Inspecting Incoming Electronics

**The Goal**: You want to check every single shipment of "Circuit Boards" arriving from suppliers to ensure no pins are bent.

1.  **Operation**: Goods Receipt
2.  **Product**: Circuit Board X500
3.  **Frequency**: Per Operation (Check every shipment)
4.  **Is Blocking**: Yes (Don't let bad boards into stock)
5.  **Template**: "Electronics Visual Inspection"

### Scenario 2: Random Sampling for Packaging

**The Goal**: You are shipping 1000 boxes and want to check the packaging on just a few of them, not all, to save time.

1.  **Operation**: Customer Delivery
2.  **Product**: [Leave Empty] (Apply to all outgoing products) or select specific.
3.  **Frequency**: Random / Per Operation (depending on desired logic, or use a specific "Random" setting if available, otherwise manual sampling instructions in the template).
    *   *Note: If specific Random freq isn't available, rely on "Per Operation" and instructions.*

---

## Best Practices

*   **Name Clearly**: Use names like "Rec: [Product] - [Check Type]" so you can scan the list easily.
*   **Don't Over-Block**: Only use **Is Blocking** for critical issues. Too many blocking checks can slow down your warehouse team.
*   **Use Templates**: Reuse the same **Inspection Template** across multiple Quality Points to keep standards consistent.
*   **Test It**: Set up the rule and try a test transfer to ensure the Quality Check appears exactly when you expect it.

---

## Troubleshooting

**Q: The Quality Check isn't appearing?**
A: Check if the **Operation Type** matches exactly. If you set it for "Manufacturing Output" but you are doing a "Goods Receipt", it won't trigger. Also, ensure the **Product** matches.

**Q: I can't validate my transfer?**
A: You likely have a **Blocking** Quality Point that failed or is pending. You must complete the check and pass it (or have a manager override it) to proceed.

---

## Related Documentation

*   [Quality Checks](quality-checks.md) - How to perform the actual check.
*   [Quality Alerts](quality-alerts.md) - What to do if a check fails.
