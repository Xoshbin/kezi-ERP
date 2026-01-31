---
title: Understanding Project Management
icon: heroicon-o-briefcase
order: 25
---

# Understanding Project Management

This guide explains **Project Management** in JMeryar ERP—how to create projects, track tasks, manage budgets, and bill clients for your work. Written for everyone (even if project management feels overwhelming!), it covers the full lifecycle from project creation to final invoicing.

---

## What is Project Management?

Imagine you're a construction company. A client wants you to build an office. You need to:
- Track every hour your workers spend on the job
- Monitor materials and equipment costs
- Stay within the budget you agreed upon
- Bill the client for your time and expenses

That's what **Project Management** helps you do—it connects your daily work to your accounting system, so you always know if a project is profitable!

**In business terms:**
Project Management is the process of planning, executing, and monitoring work to achieve specific goals within time and budget constraints. Our system links this to your financial data for complete visibility.

**The Key Elements:**
1. **Projects**: The container for all work (e.g., "Office Building for ABC Corp")
2. **Tasks**: Specific pieces of work within the project (e.g., "Foundation", "Electrical", "Finishing")
3. **Timesheets**: Records of hours worked by employees
4. **Budgets**: Planned costs vs actual spending
5. **Invoices**: Bills sent to clients based on work completed

---

## How It Works in JMeryar ERP

Our system provides a complete project lifecycle management solution integrated with accounting.

### 1. Creating a Project

Think of this as opening a new job file. You define:
- **Project Name**: What are you working on?
- **Customer**: Who are you doing it for?
- **Start/End Dates**: When does work begin and end?
- **Billing Type**: How will you charge? (Fixed price, Time & Materials, or Non-billable)
- **Budget**: How much do you expect to spend?

**Example:**
"Website Redesign for XYZ Corp" for customer XYZ Corp, starting January 1st, billed hourly.

**Where to find this:** Project Management → Projects → Create

---

## Project Statuses: The Lifecycle

Every project moves through stages:

```
    ┌─────────────────────────────────┐
    │   DRAFT                         │
    │   Project is being planned      │
    └──────────────┬──────────────────┘
                   │ Activate
                   ▼
    ┌─────────────────────────────────┐
    │   IN PROGRESS                   │
    │   Work is actively happening    │
    └──────────────┬──────────────────┘
                   │ Complete
                   ▼
    ┌─────────────────────────────────┐
    │   COMPLETED                     │
    │   All work finished             │
    └──────────────┬──────────────────┘
                   │ (Optional)
                   ▼
    ┌─────────────────────────────────┐
    │   CANCELLED                     │
    │   Project stopped early         │
    └─────────────────────────────────┘
```

**Key Rules:**
- You can only log timesheets to **In Progress** projects
- Only **Completed** projects can receive final invoices
- **Cancelled** projects freeze all activity

---

## Tasks: Breaking Down the Work

Large projects are hard to manage. Tasks help you organize work into manageable pieces.

### Creating Tasks

For our "Website Redesign" project:

| Task Name | Estimated Hours | Assigned To |
|-----------|-----------------|-------------|
| Discovery & Planning | 20 | Sarah |
| UI/UX Design | 40 | Ahmed |
| Development | 80 | Team |
| Testing & QA | 20 | Layla |
| Launch & Training | 10 | All |

**Where to find this:** Project Management → Projects → [Your Project] → Tasks tab → Create

### Task Statuses

Each task has its own lifecycle:
- **To Do**: Not started yet
- **In Progress**: Work is happening
- **Completed**: Task finished
- **Cancelled**: Task dropped

**Pro Tip:** Use task progress percentages (0-100%) to get a quick view of overall project health!

---

## Timesheets: Tracking Employee Hours

Timesheets answer the question: **"Who worked on what, and for how long?"**

### The Timesheet Workflow

```
    ┌─────────────────────────────────┐
    │   DRAFT                         │
    │   Employee enters hours         │
    └──────────────┬──────────────────┘
                   │ Submit
                   ▼
    ┌─────────────────────────────────┐
    │   SUBMITTED                     │
    │   Waiting for approval          │
    └──────────────┬──────────────────┘
                   │
        ┌──────────┴──────────┐
        │                     │
        ▼ Approve             ▼ Reject
    ┌───────────┐         ┌───────────┐
    │ APPROVED  │         │ REJECTED  │
    │ Hours     │         │ Employee  │
    │ counted!  │         │ must fix  │
    └───────────┘         └───────────┘
```

### Creating a Timesheet

1. Go to **Project Management → Timesheets → Create**
2. Select the **Employee**
3. Set the **Period** (e.g., Week of January 6-12)
4. Add **Lines** for each day/task:

| Date | Project | Task | Hours | Billable? |
|------|---------|------|-------|-----------|
| Jan 6 | Website Redesign | UI/UX Design | 8 | ✅ |
| Jan 7 | Website Redesign | UI/UX Design | 6 | ✅ |
| Jan 7 | Internal Training | N/A | 2 | ❌ |

**Important:** Billable hours will be charged to the client. Non-billable hours are internal costs.

### Approval Process

**Why require approval?**
- Managers verify hours are accurate
- Prevents padding or mistakes
- Only approved hours flow to invoicing

**If rejected:** The employee gets notified with a reason and can fix and resubmit.

---

## Project Budgets: Staying on Track

Budgets help you answer: **"Are we making or losing money?"**

### Setting Up a Budget

When creating a project budget, break it down by category:

| Budget Category | Budgeted Amount | Actual Cost | Variance |
|-----------------|-----------------|-------------|----------|
| Labor | $10,000 | $8,500 | +$1,500 ✅ |
| Materials | $5,000 | $5,800 | -$800 ⚠️ |
| Equipment | $2,000 | $1,800 | +$200 ✅ |
| **Total** | **$17,000** | **$16,100** | **+$900** |

**Where to find this:** Project Management → Budgets → Create

### Budget Tracking

The system automatically tracks:
- **Budgeted Amount**: What you planned to spend
- **Actual Amount**: What you've actually spent (from approved timesheets, vendor bills, etc.)
- **Variance**: The difference (positive = under budget ✅, negative = over budget ⚠️)
- **Utilization %**: How much of the budget has been used

**Real-time updates:** Every time a timesheet is approved or expense recorded, the budget dashboard updates automatically!

---

## Project Invoicing: Getting Paid

Finally, the goal of all this tracking—billing your client!

### Billing Types

**1. Time & Materials**
- Bill clients based on actual hours worked
- Each hour has a rate (e.g., $150/hour for developers)
- Great for: Consulting, development, creative work

**2. Fixed Price**
- Bill a set amount regardless of hours
- You quoted $20,000, you bill $20,000
- Great for: Construction, well-defined deliverables

**3. Non-Billable**
- Internal projects with no customer invoicing
- Training, R&D, internal tools
- Great for: Overhead projects

### Creating an Invoice from a Project

For **Time & Materials** projects:

1. Go to **Project Management → Invoicing → Create**
2. Select the **Project**
3. System automatically calculates:
   - Total approved billable hours
   - Hourly rate × hours = Labor amount
   - Any additional expenses to pass through
4. Review and finalize the invoice
5. Invoice is created and linked to the customer

**Example:**
- 100 billable hours at $150/hour = $15,000 labor
- $500 in materials to pass through
- **Total Invoice**: $15,500

---

## The Complete Workflow: Real-Life Example

Let's follow a project from start to finish.

### Day 1: Project Kickoff
**Ahmed creates the project:**
- Name: "Brand Identity Package"
- Customer: XYZ Corp
- Type: Time & Materials
- Budget: $8,000

**Status: Draft**

### Week 1: Work Begins
**Ahmed activates the project.**

**Status: In Progress**

**Team members log timesheets:**
- Sarah: 20 hours on logo design
- Ahmed: 10 hours on brand guidelines
- Layla: 5 hours on color palette research

**All timesheets submitted and approved.**

### Week 2: Mid-Project Check
**Ahmed checks the budget dashboard:**
- Budgeted: $8,000
- Spent so far: $5,250 (35 hours × $150/hr)
- Remaining: $2,750

Looks good! Under budget and on track.

### Week 3: Project Completion
**All tasks marked complete.**

**Ahmed creates final invoice:**
- Total hours: 50
- Rate: $150/hour
- **Invoice amount: $7,500**

Invoice sent to XYZ Corp!

**Status: Completed**

---

## Best Practices

### 1. Always Use Tasks
Don't log time directly to projects—break it into tasks. This gives you better data on where time is spent.

### 2. Submit Timesheets Weekly
Don't wait until month-end. Weekly submission keeps data fresh and catches errors early.

### 3. Review Budget Variance Weekly
Check the budget dashboard every week. Catching cost overruns early gives you time to adjust.

### 4. Set Realistic Budgets
Padding budgets by 10-15% for unexpected issues is smart planning, not dishonesty.

### 5. Close Completed Projects
Once fully invoiced, mark projects as **Completed**. This prevents accidental timesheet entries.

---

## Common Scenarios & Solutions

### Scenario 1: Client Dispute on Hours
**Problem:** Client says "You only worked 40 hours, not 50!"

**Solution:** 
1. Open the project in the system
2. View all approved timesheets with dates and descriptions
3. Share the detailed report—every hour is documented!

### Scenario 2: Budget Exceeded
**Problem:** Project is 20% over budget and not done yet.

**Solution:**
1. Review the variance report to see which categories exceeded
2. Discuss with the client about scope changes
3. Either reduce scope or negotiate additional budget approval
4. Document everything in the project notes

### Scenario 3: Employee Left Mid-Project
**Problem:** Sarah quit, and her timesheets weren't approved.

**Solution:**
1. Manager can still approve pending timesheets
2. Reassign her tasks to other team members
3. Project history is preserved—nothing is lost

---

## Dashboard & Reports

### Project Overview Widget
The main dashboard shows at a glance:
- Total active projects
- Overall budget vs actual
- Pending timesheet approvals
- Projects nearing completion

### Budget Variance Chart
Visual comparison of budget vs actual across all projects—quickly spot which projects need attention.

---

## Related Documentation

- [Creating Customer Invoices](customer-invoices.md) - Invoice details
- [Vendor Bills](vendor-bills.md) - Recording project expenses
- [Understanding Fiscal Years](understanding-fiscal-years.md) - Period management

---

## Quick Reference Card

**Creating a Project:**
1. Project Management → Projects → Create
2. Fill in customer, dates, billing type
3. Set budget expectations
4. Save (Draft)
5. Activate when ready to begin work

**Logging Time:**
1. Project Management → Timesheets → Create
2. Select employee and period
3. Add lines for each day's work
4. Submit for approval

**Checking Budget Health:**
1. Project Management → Budgets
2. Select your project
3. View variance and utilization metrics
4. Investigate any overages

**Invoicing a Project:**
1. Project Management → Invoicing → Create
2. Select the project
3. Review calculated amounts
4. Generate and send invoice

**Pro Tip:** The project list shows a status badge—green means on budget, yellow means caution, red means over budget. Use this for quick scanning!
