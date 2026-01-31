---
title: Quality Alerts
icon: heroicon-o-bell-alert
order: 11
---

# Quality Alerts: Tracking and Resolving Quality Issues

This guide explains how to manage quality alerts in the Quality Control module. Think of quality alerts as your early warning system—when a quality check fails or an issue is detected, an alert is created to track the problem from discovery through investigation to resolution.

---

## What is a Quality Alert?

A **Quality Alert** is a formal record of a quality issue that requires investigation and corrective action. It's your organization's way of documenting problems, analyzing root causes, and implementing solutions to prevent recurrence.

**Why does this matter?**
1. **Accountability**: Track who investigates and resolves quality issues.
2. **Continuous Improvement**: Learn from failures to prevent future problems.
3. **Compliance**: Maintain audit trails for ISO 9001, FDA, and other quality standards.
4. **Customer Satisfaction**: Systematically address issues before they reach customers.

**Common triggers**:
- ✅ **Failed Quality Check**: Automatically created when a quality inspection fails.
- 📋 **Manual Creation**: Quality team identifies an issue proactively.
- 📞 **Customer Complaint**: External feedback about product quality.
- 🏭 **Manufacturing Defect**: Production team discovers a problem.

---

## Where to Find It

Navigate to: **Quality Control → Quality Alerts**

Quality alerts also appear in:
- **Quality Checks**: When a check fails, it links to the created alert.
- **Dashboard**: Recent open alerts for quick visibility.
- **Product records**: All alerts related to a specific product.

> **💡 Tip**: Use the status filter to focus on **New** alerts that need immediate attention, or **In Progress** alerts you're currently investigating.

---

## The Quality Alert Lifecycle

Quality alerts follow a structured workflow to ensure systematic problem resolution:

┌──────────┐    ┌──────────────┐    ┌────────────┐    ┌──────────┐
│   New    │ ─▶ │ In Progress  │ ─▶ │  Resolved  │ ─▶ │  Closed  │
└──────────┘    └──────────────┘    └────────────┘    └──────────┘
     🔔               🔍                  ✅              ✓

### 1. New (🔔)

**Status**: A quality alert has just been created.

**What it means**:
- Issue has been identified but investigation hasn't started yet.
- Alert is waiting for assignment to a quality team member.

**Next steps**:
- Review the alert description
- Assign to the appropriate investigator
- Change status to **In Progress**

### 2. In Progress (🔍)

**Status**: Investigation is underway.

**What it means**:
- A team member is actively investigating the issue.
- Root cause analysis is being performed.
- Corrective and preventive actions are being planned.

**Key activities**:
- Document findings in the **Root Cause** field
- Plan **Corrective Action** (immediate fix)
- Plan **Preventive Action** (prevent recurrence)
- Gather evidence (photos, measurements, test results)

### 3. Resolved (✅)

**Status**: Actions have been taken to fix the issue.

**What it means**:
- Root cause has been identified
- Corrective and preventive actions have been implemented
- Solution is being monitored for effectiveness

**Requirements**:
- All CAPA fields must be documented
- Evidence of corrective action completion
- Verification that the issue is fixed

### 4. Closed (✓)

**Status**: Issue is fully resolved and verified.

**What it means**:
- Solution has been proven effective
- No further action needed
- Alert enters permanent record for audit purposes

> [!IMPORTANT]
> Once an alert is closed, it becomes part of your quality history. You cannot delete closed alerts—this maintains audit trail integrity.

---

## Key Concepts

### CAPA (Corrective and Preventive Action)

**CAPA** is the heart of quality management—it's not just fixing problems, but preventing them from happening again.

#### Root Cause

The **Root Cause** is the fundamental reason why the problem occurred. Don't confuse symptoms with causes!

**Example**:
- ❌ Symptom: "Product damaged during shipping"
- ✅ Root Cause: "Insufficient padding in packaging process—packing team not trained on fragile item procedures"

**Techniques for finding root cause**:
- **5 Whys**: Ask "why" five times to dig deeper
- **Fishbone Diagram**: Analyze man, machine, method, material, environment
- **Data Analysis**: Review quality trends, failure rates, timing patterns

#### Corrective Action

**Corrective Action** fixes the immediate problem.

**Examples**:
- Return defective batch to supplier
- Rework finished goods to meet specifications
- Quarantine affected inventory
- Adjust machine calibration

#### Preventive Action

**Preventive Action** prevents the problem from recurring.

**Examples**:
- Update supplier quality requirements
- Implement additional inspection checkpoints
- Train operators on proper procedures
- Modify equipment or process design
- Add verification steps to work instructions

> [!TIP]
> Great preventive actions address the root cause, not just the symptom. Ask yourself: "If we implement this, could the same problem happen again?"

---

### Defect Types

**Defect Types** categorize quality issues for trend analysis. Common categories include:

| Defect Type | Description | Examples |
|-------------|-------------|----------|
| **Dimensional** | Size, weight, or measurement out of tolerance | Too long, too heavy, incorrect diameter |
| **Visual** | Appearance defects | Color mismatch, scratches, dents |
| **Functional** | Product doesn't work correctly | Won't power on, leaks, breaks under load |
| **Packaging** | Damage or error in packaging | Torn box, wrong label, missing items |
| **Documentation** | Paperwork or labeling errors | Incorrect lot number, missing certificate |
| **Contamination** | Foreign material or purity issues | Dirt, rust, chemical residue |

Categorizing defects helps you:
- Identify patterns (e.g., "80% of alerts are visual defects from Supplier A")
- Focus improvement efforts where they matter most
- Track effectiveness of preventive actions

---

### Assignment

Alerts can be assigned to specific **Quality Team Members** for investigation.

**Best practices**:
- Assign based on expertise (e.g., chemical defects → lab technician)
- Set clear expectations for response time
- Track workload distribution to avoid bottlenecks

**Unassigned alerts** appear in a queue for the quality manager to triage.

---

## Managing Quality Alerts

Let's walk through the complete alert management process.

### Creating an Alert Manually

Sometimes you need to create an alert outside of the automated quality check workflow.

**Step 1: Navigate to Alerts**

Go to **Quality Control → Quality Alerts → Create New**

**Step 2: Fill in Basic Information**

| Field | What to Enter | Example |
|-------|---------------|---------|
| **Product** | The affected product | "Industrial Bolt - M12" |
| **Defect Type** | Category of issue | "Dimensional" |
| **Description** | Clear problem statement | "Customer reported 50 bolts measuring 13.2mm instead of 12mm ±0.1mm" |
| **Lot/Serial** | Specific batch (if tracked) | Lot #2024-01-A15 |

**Step 3: Assign the Alert**

- Select **Assigned To**: Choose the team member who will investigate
- If unsure, leave unassigned—your quality manager will triage it

**Step 4: Save**

Click **Save**. The alert is created with status **New**.

---

### Investigating an Alert

Once assigned, the investigator changes the status to **In Progress** and begins work.

**Step 1: Gather Information**

- Review the quality check that triggered the alert (if applicable)
- Examine affected products or materials
- Interview operators or suppliers
- Review recent process changes

**Step 2: Identify Root Cause**

Use root cause analysis techniques to dig deep. Document findings in the **Root Cause** field.

**Example**:
> "Thread diameter exceeds specification due to worn cutting tool. Tool was not replaced at scheduled maintenance interval because maintenance schedule was not followed during high-volume production week."

**Step 3: Plan Corrective Action**

What needs to be done *right now* to fix the immediate problem?

**Example**:
> "1. Replace worn cutting tool immediately. 2. Inspect all bolts produced in last 3 days. 3. Quarantine out-of-spec inventory (estimated 500 units). 4. Notify affected customers of potential shipment delay."

**Step 4: Plan Preventive Action**

What changes prevent this from happening again?

**Example**:
> "1. Implement automated tool change reminders in production system. 2. Add tool wear inspection to daily operator checklist. 3. Train production supervisors on importance of maintenance schedule adherence even during high-volume periods."

**Step 5: Resolve the Alert**

Click the **Resolve Alert** action button in the top right corner.

A modal window will appear requiring you to document your CAPA findings:

- **Root Cause**: What was the fundamental reason?
- **Corrective Action**: What immediate fix was applied?
- **Preventive Action**: What was done to prevent recurrence?
- **Scrap Items?**: Toggle this if the affecting items need to be moved to scrap immediately.

> [!WARNING]
> You cannot resolve an alert without filling in all three CAPA fields. This ensures proper documentation for every quality incident.

Click **Confirm** to save. The status will automatically change to **Resolved**.

---

### Closing an Alert

After the solution has been in place and proven effective (typically 1-4 weeks), the alert can be closed.

**Criteria for closing**:
- Corrective action completed and verified
- Preventive action implemented
- No recurrence of the issue observed
- CAPA documentation complete

Change status from **Resolved** to **Closed**.

> [!NOTE]
> Some organizations require quality manager approval before closing alerts. Check your company's quality management procedures.

---

## Integration with Other Modules

Quality alerts connect seamlessly with your operations:

### Quality Checks

- When a quality check **fails**, the system automatically creates an alert
- The alert links back to the original quality check record
- You can view which quality parameters failed directly from the alert

### Stock Management

- Alerts can be linked to specific **Lot Numbers** or **Serial Numbers**
- Helps you quarantine or recall affected inventory
- Tracks which stock movements are related to quality issues

### Purchase Management

- Vendor-related quality alerts inform purchasing decisions
- Track supplier quality performance over time
- Support objective vendor evaluation

---

## Common Scenarios

### Scenario 1: Failed Incoming Inspection

**The Situation**: You receive 100 kg of flour from a vendor. During the incoming quality inspection, moisture content measures 15% (specification: ≤13%).

**Workflow**:

1. **Quality Check Fails** → System automatically creates a Quality Alert
   - Status: **New**
   - Defect Type: "Specification Deviation"
   - Description: "Flour lot #F-2024-015 moisture content 15%, exceeds 13% limit"
   - Linked to: QC-000234 (the quality check record)

2. **Quality Manager Assigns Alert**
   - Assigned to: Sarah Chen (Purchasing Quality Lead)
   - Status changed to: **In Progress**

3. **Investigation**
   - Root Cause: "Supplier stored flour in non-climate-controlled warehouse during rainy season"
   - Corrective Action: "Reject entire 100 kg batch. Request replacement from supplier at no cost"
   - Preventive Action: "Add storage condition verification to supplier audit checklist. Require supplier to provide storage humidity logs with each shipment during rainy season (June-Sept)"

4. **Resolution**
   - Supplier accepted return
   - Replacement batch tested at 11% moisture (acceptable)
   - Alert status: **Resolved**

5. **Closure** (2 weeks later)
   - Next 3 shipments from same supplier all passed moisture tests
   - Supplier now provides humidity logs with certificate of analysis
   - Alert status: **Closed**

---

### Scenario 2: Customer Complaint Investigation

**The Situation**: A customer calls to report that 10 units of an electronic device won't power on. The units were from a shipment sent 5 days ago.

**Workflow**:

1. **Create Alert Manually**
   - Navigate to **Quality Alerts → Create New**
   - Product: "Electronic Controller Model EC-500"
   - Defect Type: "Functional"
   - Description: "Customer ABC Corp reports 10/50 units non-functional (no power-on)"
   - Lot: Traced to manufacturing lot #2024-W03

2. **Investigation Assigned**
   - Assigned to: Production Quality Engineer
   - Status: **In Progress**

3. **Root Cause Analysis**
   - Units returned for analysis
   - Found: Solder joint defect on power circuit board
   - Root Cause: "Reflow oven temperature profile incorrectly set during production run on Jan 15. Caused cold solder joints on 20% of batch (est. 100 units affected)"

4. **CAPA Documentation**
   - Corrective Action:
     - "Recall all 500 units from lot #2024-W03"
     - "Inspect and rework affected units"
     - "Replace customer's 10 units immediately with expedited shipping"
   
   - Preventive Action:
     - "Implement oven temperature verification at start of each production shift"
     - "Add solder joint inspection to in-process quality checks (currently only final QC)"
     - "Retrain operators on oven setup procedures"

5. **Resolved & Closed**
   - After implementation, no further solder defects in next 200 units produced
   - Customer confirmed replacement units working perfectly
   - Alert closed with lessons learned documented

---

### Scenario 3: Manufacturing Defect Remediation

**The Situation**: Production operator notices inconsistent paint finish on assembled products during manufacturing.

**Workflow**:

1. **Operator Creates Alert**
   - Product: "Metal Cabinet, Model C-1000"
   - Defect Type: "Visual"
   - Description: "Uneven paint coverage, visible streaks on 30% of units produced today"
   - Status: **New**

2. **Immediate Action**
   - Production supervisor halts production line
   - Assigns alert to: Paint Process Specialist
   - Status: **In Progress**

3. **Investigation**
   - Root Cause: "Paint sprayer nozzle partially clogged. Last nozzle cleaning was 3 weeks ago (schedule: every 2 weeks)"
   - Immediate Finding: Maintenance log shows cleaning was skipped due to technician illness

4. **CAPA**
   - Corrective Action:
     - "Clean and replace sprayer nozzle"
     - "Inspect and rework 45 units with visible defects"
     - "Scrap 8 units with severe finish issues"
   
   - Preventive Action:
     - "Add backup maintenance technician for critical equipment"
     - "Implement automated maintenance scheduling with email reminders"
     - "Cross-train production operators on basic sprayer nozzle inspection"

5. **Verification & Closure**
   - Production resumed with perfect finish quality
   - No defects observed for 2 weeks
   - Alert status: **Resolved** → **Closed**

---

## Best Practices

### 🎯 Timely Investigation

- **Respond to New alerts within 24 hours**: Even if just to assign and acknowledge
- **Complete investigations within 1 week**: For most issues (complex ones may take longer)
- **Document as you go**: Don't wait until the end to fill in CAPA fields

### 📋 Thorough Documentation

- **Be specific in descriptions**: "Thread diameter 13.2mm" not "Wrong size"
- **Use measurable language**: "15% moisture" not "Too wet"
- **Include evidence**: Reference photos, test reports, measurements
- **Link related records**: Quality checks, stock moves, customer complaints

### 🔁 Close the Loop

- **Verify corrective actions work**: Don't just implement and forget
- **Monitor preventive actions**: Are they actually preventing recurrence?
- **Review trends monthly**: Are the same types of defects recurring?
- **Update procedures**: Incorporate lessons learned into work instructions

### 👥 Team Collaboration

- **Assign based on expertise**: Not all alerts are created equal
- **Balance workload**: Don't overload one person
- **Escalate when stuck**: If investigation isn't progressing, involve management
- **Share learnings**: Review closed alerts in team meetings

### 📊 Use Data for Improvement

- **Track defect types**: What's your #1 quality issue?
- **Analyze by supplier/product/process**: Where should you focus improvement?
- **Measure closure time**: Are investigations taking too long?
- **Monitor recurrence**: Are preventive actions actually preventing?

---

## Troubleshooting

### Common Questions

**Q: Why was an alert created automatically?**

A: Alerts are auto-created when:
1. A **Quality Check** fails (specifically if it is marked as **Blocking**)
2. The quality control point is configured to trigger alerts on failure

To prevent auto-creation, adjust your quality control point settings, but note that failing a blocking check will always stop the workflow and likely generate an alert for accountability.

**Q: Can I delete an alert?**

A: **Draft alerts** can be deleted, but alerts with status **New**, **In Progress**, **Resolved**, or **Closed** cannot be deleted for audit compliance. If created in error, you can:
- Close it immediately with a note: "Created in error—no action needed"
- Keep it in your system as documentation

**Q: What if I don't know the root cause yet?**

A: That's fine! Keep the status as **In Progress** while you investigate. Document what you know so far, and update the **Root Cause** field when your investigation concludes. Don't rush to resolve—thorough investigation is more important than speed.

**Q: How long should an alert stay in "Resolved" before closing?**

A: It depends on your quality system requirements. Common practices:
- **Low-risk issues**: 1-2 weeks verification
- **Medium-risk**: 2-4 weeks
- **High-risk/critical**: 4-8 weeks or until next audit

The goal is to ensure preventive actions are actually working.

**Q: Can I reopen a closed alert?**

A: No. Once closed, an alert is locked for audit integrity. If the same issue recurs, create a **new alert** and reference the previous one in the description. This helps you track true recurrence vs. new problems.

---

## Frequently Asked Questions

**Q: Who can create quality alerts?**

A: Any user with quality control permissions can create alerts manually. Alerts are also auto-created by failed quality checks regardless of who performed the check.

**Q: How do I filter alerts by product or time period?**

A: Use the filter options at the top of the Quality Alerts list:
- **Status**: New, In Progress, Resolved, Closed
- **Assigned To**: View your own alerts or unassigned items
- **Date Range**: Use the date picker to narrow by created date

**Q: Can alerts be exported for reporting?**

A: Yes! Use the **Export** button on the Quality Alerts list page to download data to Excel or CSV. This is useful for:
- Monthly quality reports
- Supplier scorecards
- Audit documentation

**Q: What's the difference between Corrective and Preventive Action?**

A: Simple rule of thumb:
- **Corrective** = Fix the problem *now* (immediate reaction)
- **Preventive** = Stop it from happening *again* (future prevention)

Both are important! Corrective action contains the damage; preventive action improves the system.

---

## Related Documentation

- [Quality Checks](quality-checks.md) - How quality inspections trigger alerts
- [Quality Control Points](quality-control-points.md) - Configuring automatic alert triggers
- [Stock Picking](stock-picking.md) - Quarantining inventory based on quality alerts
- [Vendor Management](vendor-bills.md) - Using quality alerts in supplier evaluation

---

## Glossary

- **Quality Alert**: A record of a quality issue requiring investigation and corrective action.
- **CAPA**: Corrective and Preventive Action—the systematic approach to fixing problems and preventing recurrence.
- **Root Cause**: The fundamental reason why a quality issue occurred.
- **Corrective Action**: Immediate steps taken to fix a quality problem.
- **Preventive Action**: Long-term changes to prevent quality problems from recurring.
- **Defect Type**: A category used to classify types of quality issues (e.g., dimensional, visual, functional).
- **Assignment**: Designating a specific team member to investigate and resolve an alert.
- **Status**: Current stage of the alert lifecycle (New, In Progress, Resolved, Closed).
