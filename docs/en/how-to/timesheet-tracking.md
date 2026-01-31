---
title: Understanding Timesheet Tracking
icon: heroicon-o-clock
order: 26
---

# Understanding Timesheet Tracking

This guide explains **Timesheet Tracking**—how employees record their work hours, how managers approve them, and how the system ensures accurate billing and payroll. Written for both employees and managers, it covers everything from daily time logging to approval workflows.

---

## What is a Timesheet?

Think of a timesheet as a daily work diary. It answers:
- **What did you work on?**
- **For how long?**
- **Was it billable to a client?**

**In business terms:**
A timesheet is a method for recording the amount of time an employee spends on each task or project. This data drives payroll, client billing, and project costing.

**Why is it important?**
1. **For employees**: Proves your work contribution
2. **For managers**: Ensures projects are staffed appropriately
3. **For accounting**: Accurate billing and cost tracking
4. **For HR**: Payroll and overtime calculations

---

## The Timesheet Structure

In JMeryar ERP, timesheets follow a **header-line** structure:

### The Header (Timesheet)
Contains period information:
- **Employee**: Who is logging time
- **Start Date**: Beginning of the period (e.g., Monday)
- **End Date**: End of the period (e.g., Sunday)
- **Status**: Draft, Submitted, Approved, or Rejected
- **Total Hours**: Automatically calculated

### The Lines (Timesheet Lines)
Each line represents work on a specific day:
- **Date**: When the work happened
- **Project**: Which project
- **Task**: What specific work (optional but recommended)
- **Hours**: Time spent
- **Description**: Brief note about the work
- **Billable**: Can this be charged to the client?

---

## The Timesheet Workflow

```
    ┌─────────────────────────────────────────────────────┐
    │                     EMPLOYEE                        │
    │                                                     │
    │   ┌───────────┐    ┌───────────┐    ┌───────────┐  │
    │   │  CREATE   │───▶│  FILL IN  │───▶│  SUBMIT   │  │
    │   │  New      │    │  Hours    │    │  For      │  │
    │   │  Timesheet│    │  Daily    │    │  Approval │  │
    │   └───────────┘    └───────────┘    └─────┬─────┘  │
    └───────────────────────────────────────────┼────────┘
                                                │
    ┌───────────────────────────────────────────┼────────┐
    │                     MANAGER                │        │
    │                                           ▼        │
    │           ┌────────────────────────────────────┐   │
    │           │         REVIEW TIMESHEET           │   │
    │           └───────────────┬───────────────┬────┘   │
    │                           │               │        │
    │                    ┌──────┴──────┐ ┌──────┴──────┐ │
    │                    │   APPROVE   │ │   REJECT    │ │
    │                    │   Hours     │ │   With      │ │
    │                    │   Counted!  │ │   Reason    │ │
    │                    └─────────────┘ └──────┬──────┘ │
    └────────────────────────────────────────────────────┘
                                                │
                           ┌────────────────────┘
                           │
                           ▼
    ┌─────────────────────────────────────────────────────┐
    │            EMPLOYEE FIXES & RESUBMITS               │
    └─────────────────────────────────────────────────────┘
```

---

## For Employees: Logging Your Time

### Step 1: Create a New Timesheet

1. Go to **Project Management → Timesheets → Create**
2. The system automatically selects **you** as the employee
3. Set the **period** (usually one week):
   - Start Date: Monday of your week
   - End Date: Sunday of your week

### Step 2: Add Your Daily Hours

Click **Add Line** for each block of work:

| Field | What to Enter | Example |
|-------|---------------|---------|
| Date | When you did the work | January 8, 2026 |
| Project | Which project | Website Redesign |
| Task | Specific task (if applicable) | UI/UX Design |
| Hours | How long you worked | 4 |
| Description | Brief note | "Created wireframes for homepage" |
| Billable | Should client pay for this? | ✅ Yes |

**Pro Tip:** Be specific in descriptions! "Worked on project" is unhelpful. "Created 3 wireframe mockups for client review" is much better.

### Step 3: Review Your Totals

Before submitting, check:
- **Total hours for the week**: Does it match your expected work hours?
- **Billable vs non-billable split**: Is it reasonable?
- **Missing days**: Did you forget to log anything?

### Step 4: Submit for Approval

Once you're confident everything is accurate:
1. Click **Submit**
2. Status changes from `Draft` to `Submitted`
3. Your manager receives a notification

**Important:** After submission, you **cannot edit** the timesheet until it's either approved (locked forever) or rejected (returned for corrections).

---

## For Managers: Approving Timesheets

### Reviewing Submitted Timesheets

1. Go to **Project Management → Timesheets**
2. Filter by **Status: Submitted**
3. Click on a timesheet to review

### What to Check

**1. Hours are reasonable:**
- Did they really work 12 hours on Tuesday?
- Total hours align with expected work schedule?

**2. Projects are correct:**
- Employee was actually assigned to these projects?
- Hours match project progress expectations?

**3. Billable flag is accurate:**
- Client-facing work marked billable? ✅
- Internal meetings marked non-billable? ✅

**4. Descriptions are helpful:**
- Could you explain this work to the client?
- Enough detail for future reference?

### Approving a Timesheet

If everything looks good:
1. Click the **Approve** action
2. Confirm the approval
3. Status changes to `Approved`

**What happens next:**
- Hours are locked (immutable record)
- Hours flow to budget calculations
- Billable hours become available for invoicing
- Employee sees approval in their notifications

### Rejecting a Timesheet

If something needs correction:
1. Click the **Reject** action
2. Enter a **reason** (required)
3. Status changes to `Rejected`

**Example rejection reasons:**
- "Please add descriptions to Jan 7 and Jan 8 entries"
- "8 hours on Project X seems high—can you clarify?"
- "Internal meeting should be non-billable"

**The employee then:**
1. Receives notification with your reason
2. Edits the timesheet to correct issues
3. Resubmits for your review

---

## Timesheet Best Practices

### For Employees

**1. Log time daily**
Don't wait until Friday to remember what you did Monday. Enter hours at the end of each day while it's fresh.

**2. Use descriptive notes**
Good: "Reviewed client feedback on mockups, made 5 revisions, exported final files"
Bad: "Design work"

**3. Submit on time**
Most companies expect timesheets submitted by Friday or Monday. Late submissions delay payroll and billing.

**4. Check your totals**
If your normal week is 40 hours and you logged 55, something's wrong (or you need to request overtime approval!).

**5. Ask when unsure**
Not sure if something is billable? Ask before submitting—it's harder to fix after approval.

### For Managers

**1. Review promptly**
Employees waiting for approval can't get paid or move on. Review within 24-48 hours.

**2. Be specific in rejections**
"Fix it" is unhelpful. Tell them exactly what needs changing.

**3. Spot-check descriptions**
Occasionally verify time descriptions match actual deliverables.

**4. Look for patterns**
Same task logged 100 hours over 2 weeks? Maybe the estimate was wrong, not the employee.

**5. Trust but verify**
Don't micromanage every hour, but do periodic audits.

---

## Common Scenarios & Solutions

### Scenario 1: Forgot to Log Time
**Problem:** It's Friday, and you forgot to log Monday's work.

**Solution:**
1. Log it now with Monday's date
2. Be as accurate as possible with hours
3. Add a note: "Logged late due to oversight"
4. Submit as normal

**Prevention:** Set a daily reminder alarm!

### Scenario 2: Worked on Multiple Projects in One Day
**Problem:** You spent 4 hours on Project A and 4 hours on Project B.

**Solution:**
Create **two lines** with the same date:
- Line 1: Jan 8 | Project A | 4 hours
- Line 2: Jan 8 | Project B | 4 hours

**The system handles it perfectly!**

### Scenario 3: Client Meeting—Billable or Not?
**Problem:** You had a 2-hour meeting with the client about project requirements.

**Solution:**
**Yes, this is billable!** Your expertise and time have value. The client is paying for your attention during that meeting.

**Non-billable examples:**
- Internal team meetings about the project
- Training/learning time
- Administrative tasks

### Scenario 4: Manager is on Vacation
**Problem:** Your manager can't approve your timesheet.

**Solution:**
1. Another authorized manager can approve
2. Or wait for their return (but communicate the delay)
3. Emergency: Ask the system admin to temporarily grant approval rights

### Scenario 5: Made a Mistake After Approval
**Problem:** You realized an approved timesheet has an error.

**Solution:**
Approved timesheets are **immutable** (can't be changed). To correct:
1. Notify your manager and accounting
2. They will create an adjustment timesheet or journal entry
3. Document the correction thoroughly

**Why so strict?** Because approved time affects billing and financials. Changing history would break the audit trail!

---

## Timesheet Reports

### Employee View
- **My Timesheets**: All your submissions and their statuses
- **Hours This Week/Month**: Quick summary
- **Pending Approvals**: Timesheets waiting for review

### Manager View
- **Team Timesheets**: All submissions from your team
- **Pending Approvals**: What needs your action
- **Overtime Report**: Who's exceeding standard hours

### Organization View
- **All Timesheets**: Company-wide visibility
- **Billable vs Non-Billable**: Total hours breakdown
- **Project Time Analysis**: Hours spent per project

---

## Security & Permissions

| Role | Create Own | View Team | Approve | Edit After Submit |
|------|------------|-----------|---------|-------------------|
| **Employee** | ✅ | ❌ | ❌ | ❌ |
| **Team Lead** | ✅ | ✅ | ✅ | ❌ |
| **Manager** | ✅ | ✅ | ✅ | ❌ |
| **Admin** | ✅ | ✅ | ✅ | ❌ |

**Note:** Nobody can edit after submission—this ensures data integrity.

---

## Related Documentation

- [Project Management](project-management.md) - Project creation and management
- [Customer Invoices](customer-invoices.md) - Billing based on timesheet hours
- [Payments](payments.md) - Processing payroll

---

## Quick Reference Card

**Creating a Timesheet (Employee):**
1. Project Management → Timesheets → Create
2. Set the week period
3. Add lines for each day's work
4. Review totals
5. Submit for approval

**Approving a Timesheet (Manager):**
1. Project Management → Timesheets
2. Filter by Status: Submitted
3. Review hours and descriptions
4. Approve or Reject with reason

**Checking Your Status:**
- 🟡 **Draft**: Still working on it
- 🔵 **Submitted**: Waiting for approval
- 🟢 **Approved**: Hours counted!
- 🔴 **Rejected**: Fix and resubmit

**Pro Tip:** The dashboard shows a "Pending Timesheets" count—managers, check this daily!
