---
title: Processing Your First Payroll
icon: heroicon-o-academic-cap
---

# Tutorial: Processing Your First Payroll

In this tutorial, we will process a monthly salary for an employee named **Azad**. This scenario includes a base salary and a deduction for two days of unpaid leave.

### The Scenario
- **Employee**: Azad
- **Monthly Base Salary**: $1,000
- **Unpaid Leave**: 2 Days
- **Period**: January 2026

---

### Phase 1: Creating the Draft

1. Navigate to **HR → Payrolls** and click **New Payroll**.
2. Select **Azad** from the employee list.
3. Set the date range to **January 1 to January 31, 2026**.
4. Notice how the **Base Salary** automatically fills as $1,000.

---

### Phase 2: Calculating the Deduction

Azad was absent for 2 days without pay. We need to deduct this from his salary.

1. **Calculate the daily rate**: 
   $1,000 / 22 working days ≈ **$45.45 per day**.
2. **Calculate the total deduction**: 
   2 days × $45.45 = **$90.90**.
3. In the payroll form, find the **General Deductions** section.
4. Enter **$90.90** and add a note: "Unpaid Leave (2 days)".
5. The **Net Salary** should now show **$909.10**.

---

### Phase 3: Posting to Accounting

1. Click **Approve**. 
2. The status changes to **Processed**. 
3. Behind the scenes, Kezi has created a journal entry:
   - **Debit**: Salary Expense ($1,000.00)
   - **Credit**: Accrued Salaries ($1,000.00)
   *(Note: The deduction is reflected in the final net amount when the liability is cleared).*

---

### Phase 4: Completing the Payment

1. Click **Pay Employee**.
2. Select your **Main Bank Account**.
3. Set the payment date to today.
4. Click **Record Payment**.
5. The status is now **Paid**. You have successfully completed the payroll cycle!

---

### Next Steps
- [Explanation: Payroll Workflows](../explanation/payroll-workflows.md)
- [How-to: Process Payroll](../how-to/process-payroll.md)
