---
title: Quality Checks
icon: heroicon-o-clipboard-document-check
order: 10
---

# Quality Checks: Ensuring Product Quality

This guide explains how to perform and manage quality inspections within the Quality Control module. Think of quality checks as your systematic way to verify that products meet your standards—whether receiving from vendors, manufacturing in-house, or shipping to customers.

---

## What is a Quality Check?

A **Quality Check** is an inspection performed to verify that a product meets defined quality standards. It's triggered automatically or manually during key operations like receiving goods, manufacturing, or before delivery.

**Why does this matter?**
1. **Customer Satisfaction**: Catch defects before products reach customers.
2. **Vendor Accountability**: Document quality issues with suppliers.
3. **Process Improvement**: Track quality trends to improve operations.
4. **Compliance**: Maintain records for audits and certifications.

---

## Where to Find It

Navigate to: **Quality Control → Quality Checks**

Quality checks also appear automatically in:
- **Stock Picking**: When quality control points are configured.
- **Purchase Receipts**: For incoming goods inspection.
- **Manufacturing Orders**: For production quality verification.

> **💡 Tip**: Use the status filter to quickly find checks that need attention—focus on "In Progress" items first.

---

## The Quality Check Workflow

Quality checks follow a structured process from creation to completion:

┌─────────────┐    ┌──────────────┐    ┌─────────────────────────────┐
│    Draft    │ ─▶ │ In Progress  │ ─▶ │ Passed / Failed / Conditional │
└─────────────┘    └──────────────┘    └─────────────────────────────┘
      📝                  🔍                      ✅ / ❌ / ⚠️

### 1. Check Creation (Draft)

A quality check is created when:
- A **Quality Control Point** is triggered by an operation (e.g., receiving goods).
- A user manually creates a check for a specific product.

**Key Information Captured**:
- **Product**: What's being inspected.
- **Lot/Serial Number**: Specific batch or unit (if tracked).
- **Source**: The operation that triggered the check (e.g., a purchase receipt).

### 2. Inspection (In Progress)

An authorized **Inspector** performs the actual quality check:
1. Open the quality check record.
2. Review the inspection parameters (dimensions, color, functionality, etc.).
3. Record measurements or observations for each parameter.
4. Mark each check line as Pass or Fail.

> [!NOTE]
> Inspection parameters are defined in **Quality Inspection Templates** linked to the product or control point.

### 3. Final Result

Based on the check line results, the overall status becomes:

| Status | Meaning | Next Steps |
|--------|---------|------------|
| **✅ Passed** | All parameters met standards | Goods can proceed (received, shipped, etc.) |
| **❌ Failed** | Critical defects found | A **Quality Alert** is automatically generated. The operation is blocked if the check was mandatory. You may need to scrap items or resolve the alert to proceed. |
| **⚠️ Conditionally Accepted** | Minor issues, but usable | Documented for tracking; goods may proceed with restrictions |

---

## Key Concepts

### Quality Control Points

**Quality Control Points** define **when** and **where** quality checks should happen.

**Trigger Operations**:
- **Goods Receipt**: Inspect incoming materials from vendors.
- **Internal Transfer**: Check quality during warehouse moves.
- **Manufacturing Output**: Verify finished goods quality.
- **Customer Delivery**: Final inspection before shipping.

**Trigger Frequency**:
- **Per Operation**: Every time the operation occurs.
- **Per Product**: Once for each product type.
- **Per Quantity**: When a quantity threshold is reached (e.g., every 100 units).

**Blocking Checks**: If set, operations cannot complete until the quality check passes.

### Inspection Templates

Templates define the specific parameters to check (e.g., weight, dimensions, visual defects). These are linked to products or control points.

### Inspectors

Designated users who have authority to perform and approve quality checks. Assign inspectors based on expertise (e.g., chemical inspections vs. mechanical).

---

## Performing a Quality Check

Let's walk through a real inspection step by step.

### Step 1: Find the Check

Navigate to **Quality Control → Quality Checks**

Or, if triggered by a receipt:
1. Go to **Inventory → Stock Picking**
2. Open the receipt record
3. Click the linked **Quality Check** in the workflow section

### Step 2: Start the Inspection

Click **Start Inspection** to change status to **In Progress**.

### Step 3: Evaluate Each Parameter

You'll see a table of inspection lines (check parameters):

| Parameter | Type | Expected | Actual | Result |
|-----------|------|----------|--------|--------|
| Weight | Measurement | 500g ± 10g | *Enter value* | Pass/Fail |
| Color | Visual | Red | *Confirm* | Pass/Fail |
| Packaging Intact | Yes/No | Yes | *Select* | Pass/Fail |

For each line:
1. Perform the physical check.
2. Record the actual measurement or observation.
3. Mark as **Pass** or **Fail**.

### Step 4: Add Notes (Optional)

Use the **Notes** field to document:
- Environmental conditions during inspection.
- Specific defects observed.
- Reasons for conditional acceptance.

### Step 5: Complete the Check

Click **Complete Inspection**. The system will:
- Evaluate all check lines.
- Set overall status (Passed/Failed/Conditionally Accepted).
- If failed, automatically create a **Quality Alert**.

---

## Common Scenarios

### Scenario 1: Inspecting Raw Materials at Receipt

**The Situation**: You receive 100 kg of flour from a vendor. A quality control point is configured to inspect all flour receipts.

**Here's what happens:**

1. **Warehouse team** creates a receipt in **Stock Picking** for 100 kg of flour.
2. **System automatically** generates a Quality Check (status: Draft).
3. **Inspector** opens the check and starts inspection.
4. **Checks performed**:
   - Moisture content: 12% (Target: ≤ 13%) → **Pass**
   - Visual inspection: No lumps → **Pass**
   - Packaging: Few bags slightly torn → **Fail**

5. **Result**: Overall status = **Failed**.
6. **Quality Alert** is created automatically:
   - Assigned to: Purchasing Manager
   - Description: "Damaged packaging on 5 bags out of 20"
   - Action required: Contact vendor for replacement

7. **Decision**: Accept 15 good bags, reject 5 damaged ones.

### Scenario 2: Final Inspection Before Customer Shipment

**The Situation**: A customer order for 50 electronic devices is ready to ship.

**Workflow**:
1. Quality Control Point triggers a check at **Customer Delivery**.
2. Inspector samples 5 devices (10% sample rate from control point config).
3. Tests power-on, functionality, and cosmetic condition.
4. **All pass**.
5. Quality Check marked as **Passed**.
6. Delivery operation proceeds automatically (if blocking check was enabled).

---

## When Quality Checks Fail

If a quality check fails, a **Quality Alert** is automatically generated.

**The alert includes**:
- **Failed Check**: Link to the original quality check.
- **Defect Type**: Category of the problem (e.g., "Dimensional", "Visual", "Functional").
- **Severity**: Based on how many parameters failed.
- **Assignment**: Routed to the responsible team (QA, Purchasing, Production).

**Remediation Actions**:
1. **Root Cause Analysis**: Investigate why the failure occurred.
2. **Corrective Action**: Immediate fix (e.g., rework, return to vendor).
3. **Preventive Action**: Long-term improvement to avoid recurrence.

Once resolved, the alert is marked as **Closed** with documented actions taken.

---

## Integration with Other Modules

Quality checks seamlessly connect with your operations:

### Stock Picking (Inventory)
- Quality checks trigger on goods receipts and deliveries.
- If blocking enabled, stock moves are paused until check passes.

### Purchase Receipts
- Vendor bill processing can be held pending quality approval.
- Failed checks trigger vendor notifications.

### Manufacturing Orders
- In-process and final quality checks ensure production standards.
- Failed checks can halt production runs.

---

## Best Practices

### 🎯 Set Up Control Points Strategically
- Don't check everything—focus on critical operations.
- **Incoming materials**: High-risk vendors or critical components.
- **Manufacturing**: Final output and critical process steps.
- **Outgoing shipments**: Customer-facing products.

### 📋 Use Sampling for High-Volume Operations
- Configure frequency as "Per Quantity" to avoid inspecting every single unit.
- Example: Check 1 out of every 50 units for routine products.

### 👥 Train and Certify Inspectors
- Only assign inspectors with proper training for specific checks.
- Rotate inspectors to avoid complacency.

### 📊 Review Quality Trends
- Regularly review failed checks to identify patterns.
- Work with vendors or production teams to address recurring issues.

---

## Troubleshooting

### Common Questions

**Q: Why didn't a quality check trigger automatically?**

A: Three common reasons:
1. **No Control Point**: Ensure a Quality Control Point is defined for that operation + product combination.
2. **Inactive Point**: Check that the control point's `Active` flag is enabled.
3. **Frequency Threshold**: If using "Per Quantity", the quantity may not have reached the threshold yet.

**Q: Can I edit a completed quality check?**

A: No. Once marked as Passed or Failed, the check is locked to maintain audit integrity. If an error was made, create a new quality check or add corrective notes in the linked Quality Alert.

**Q: Who can perform quality checks?**

A: Only users with the "Quality Inspector" role or permission can record check results. This ensures qualified personnel perform inspections.

**Q: What if we accept goods conditionally?**

A: Use the **Conditionally Accepted** status for items with minor issues that don't warrant rejection. Document the specific conditions in the Notes field (e.g., "Accepted with 2% over-tolerance; usable for non-critical applications").

---

## Frequently Asked Questions

**Q: How do I create a quality check manually?**

A: Go to **Quality Control → Quality Checks → New Quality Check**. Select the product, lot (if applicable), and inspection template. This is useful for ad-hoc checks outside of automated control points.

**Q: Can quality checks be performed offline?**

A: Not currently. Inspectors must have system access to record results. For field inspections, consider using mobile devices with the system accessed via browser.

**Q: How long are quality records retained?**

A: Quality checks and alerts are permanent records (for compliance and audit purposes). They can be archived after a retention period but not deleted.

---

## Related Documentation

- [Quality Alerts](quality-alerts.md) - Managing and resolving quality issues
- [Quality Control Points](quality-control-points.md) - Configuring automatic inspections
- [Stock Picking](stock-picking.md) - Inventory operations integration
- [Purchase Management](vendor-bills.md) - Vendor quality tracking

---

## Glossary

- **Quality Check**: An inspection record for verifying product quality.
- **Inspector**: A user authorized to perform quality inspections.
- **Control Point**: A configuration defining when/where quality checks trigger.
- **Blocking Check**: A quality check that prevents an operation from completing until passed.
- **Quality Alert**: A record of a quality issue requiring remediation.
- **Conditionally Accepted**: Products accepted with documented minor issues.
