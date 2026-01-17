---
title: Payroll Processing
icon: heroicon-o-currency-dollar
order: 50
---

# Payroll Processing: Managing Employee Compensation

This guide explains how to manage and process payroll within the HR Module. From generating salary calculations to posting journal entries and making payments, we'll walk you through the entire lifecycle.

---

## What is a Payroll Run?

A **Payroll Run** (or simply a Payroll record) represents the salary calculation for a specific employee during a defined period (usually a month). It brings together the employee's contract terms, attendance data, and any specific adjustments (like bonuses or deductions) to determine the Final Net Pay.

**Why does this matter?**
1. **Accuracy**: Ensures employees are paid correctly based on their signed contracts.
2. **Accounting Compliance**: Automatically generates the necessary journal entries to record salary expenses and liabilities.
3. **Audit Trail**: Maintains a clear record of who was paid, how much, and who approved the payment.

---

## Where to Find It

Navigate to: **HR → Payrolls**

You can also view individual payroll records from an **Employee profile** under the related records section.

---

## The Payroll Workflow

Processing payroll in Jmeryar follows a structured four-stage workflow:

┌─────────┐      ┌───────────┐      ┌─────────┐      ┌─────────┐
│  Draft  │ ──▶ │ Processed  │ ──▶ │  Paid   │ ──▶ │ Closed  │
└─────────┘      └───────────┘      └─────────┘      └─────────┘
    📝               ✅                💰               🔒

### 1. Creating a New Payroll (Draft)

When you create a new payroll record, you specify the **Employee**, the **Period** (Start/End Date), and the **Pay Date**.

| Field | Description |
|-------|-------------|
| **Employee** | The staff member receiving payment. |
| **Period dates** | Usually the 1st to the last day of the month. |
| **Base Salary** | Automatically fetched from the active employment contract. |
| **Allowances** | Pre-filled from the contract (Housing, Transport, etc.). |

### 2. Review and Calculation

Once the basic details are entered, the system calculates the totals based on the provided numbers. 

> [!NOTE]
> Currently, the system supports manual entry and adjustment of components like overtime, bonuses, and specific deductions during the draft stage.

### 3. Approval (Posting)

After reviewing the numbers, an authorized manager must click **Approve**.

**What happens behind the scenes:**
- The status changes to **Processed**.
- A **Journal Entry** is automatically created in the Accounting module.
- The system records a **Debit** to Salary Expense and a **Credit** to Salary Payable.

┌─────────────────────────────────────────────────────────────┐
│  Dr. Salary Expense (601001)        $1,000.00               │
│      Cr. Accrued Salaries (201001)           $1,000.00      │
└─────────────────────────────────────────────────────────────┘

### 4. Making the Payment

Once processed, the **Pay Employee** action becomes available. Clicking this will:
1. Open a payment dialog.
2. Create a record in the **Payment** module for the Net Salary amount.
3. Link the payment to the payroll record for easy reference.
4. Mark the payroll as **Paid**.

---

## Example Scenario: Unpaid Leave Impact

Let's look at how attendance affects the final payment.

**The Situation**: 
An employee, Azad, has a monthly base salary of **$1,000**. In January, he took **2 days of unpaid leave**.

**Calculation Logic**:
- **Total Working Days in Month**: 22 days (example).
- **Daily Rate**: $1,000 / 22 = $45.45
- **Unpaid Leave Deduction**: 2 days × $45.45 = **$90.90**

**In the Payroll Record**:
1. Select Azad and the January period.
2. The Base Salary shows $1,000.
3. Under **Other Deductions**, enter $90.90 with the note "Unpaid Leave - 2 Days".
4. The **Net Salary** will automatically update to **$909.10**.

---

## Frequently Asked Questions

**Q: Can I edit a payroll after it's been approved?**
A: No. Once approved (Processed), it is locked to maintain accounting integrity. If an error is found, you must cancel or reverse the transaction or use an adjustment document.

**Q: Where do I see the Journal Entry?**
A: Open the payroll record; the Journal Entry is linked in the **System Information** or **Workflow** section. Click it to view the full ledger details.

**Q: How do multi-currency payrolls work?**
A: The system uses the currency defined in the employee's contract. If it differs from the company's base currency, the system applies the exchange rate active on the **Pay Date**.

---

## Best Practices

- **Verify Contracts First**: Ensure the employee has an active, approved contract before starting the payroll run.
- **Batch Processing**: Use filters to group employees by department for easier review.
- **Double-Check Deductions**: Always review the "Other Deductions" field for any manual adjustments needed for the specific period.

---

## Related Documentation
- [Employee Management](employee-management.md)
- [Leave Management](leave-management.md)
- [Journal Entries Guide](../Developers/journal_entry_flow_report.md)
