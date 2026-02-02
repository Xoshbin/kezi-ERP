---
title: Recurring Templates
icon: heroicon-o-arrow-path
order: 10
---

# Recurring Templates: Automate Your Regular Transactions

This guide explains how to use Recurring Templates to automate repetitive accounting entries. Whether it's a monthly rent payment, a subscription invoice, or a daily accrual, recurring templates save you time and ensure you never miss a transaction.

---

## What are Recurring Templates?

Think of a Recurring Template like an automatic scheduler for your accounting documents. Instead of manually typing out the same invoice or journal entry every month, you set it up once, tell the system how often to repeat it, and let it do the work for you.

**Why does this matter?**
1. **Saves Time**: automated creation of repetitive documents.
2. **Consistency**: ensures the same accounts and amounts are used every time.
3. **Reliability**: prevents forgetting manual entries like rent or subscriptions.

---

## Where to Find It

Navigate to: **Accounting → Recurring Templates**

You can view all your active and paused templates here, see when they will run next, and check their history.

---

## Creating a Recurring Template

Let's walk through creating a new template step by step.

### Step 1: Start Fresh

Navigate to **Accounting → Recurring Templates** and click the **New Recurring Template** button.

### Step 2: General Information

Fill in the basic details:

| Field | Description | Example |
|-------|-------------|---------|
| **Template Name** | A descriptive name for your reference | "Monthly Office Rent" |
| **Status** | Controls if the template acts | **Active** (runs) or **Active** (paused) |
| **Target Type** | What document to create | **Journal Entry** or **Invoice** |

> [!TIP]
> Use clear names like "Weekly Catering Bill" instead of just "Bill" so you can find them easily later.

### Step 3: Scheduling

Tell the system when and how often to run this template:

- **Frequency**: How often to repeat (e.g., Monthly, Yearly).
- **Interval**: How many of the frequency units to wait.
    - Example: Frequency = Monthly, Interval = 1 → Every month.
    - Example: Frequency = Monthly, Interval = 3 → Every quarter.
- **Start Date**: The date of the first occurrence (or next occurrence if in the future).
- **Next Run Date**: Automatically calculated, but you can adjust it to skip or delay the next run.

### Step 4: Template Data

Depending on your **Target Type**, the detailed fields will change.

#### Option A: Journal Entry Target
Use this for manual adjustments, payroll accruals, or depreciation.

- **Journal**: Select the journal (e.g., Miscellaneous Operations).
- **Currency**: The currency for the entry.
- **Lines**: Add debit and credit lines just like a regular journal entry.
    - **Account**: The general ledger account.
    - **Debit/Credit**: The amounts.
    - **Partner**: (Optional) Associate with a customer/vendor.

#### Option B: Invoice Target
Use this for customer subscriptions or recurring vendor bills.

- **Customer/Vendor**: Who is this for?
- **Payment Terms**: When is payment due (e.g., Net 30).
- **Lines**: Add products or services.
    - **Product**: Select the item.
    - **Quantity**: How many.
    - **Unit Price**: Cost per item.
    - **Tax**: Applicable tax.

### Step 5: Save

Click **Create** to save your template. The system will now automatically generate the document on the "Next Run Date".

---

## Understanding Target Types

| Target Type | Best For | Output |
|-------------|----------|--------|
| **Journal Entry** | Internal transfers, accruals, depreciation | Creates a draft Journal Entry in the specified Journal. |
| **Invoice** | Customer subscriptions, Retainers | Creates a draft Customer Invoice. |

> [!NOTE]
> Generated documents are usually created in **Draft** status so you can review and post them manually.

---

## Best Practices

### 📅 Scheduling
- **Set realistic start dates**: If you set a start date in the past, the system might try to generate caught-up entries or start from today.
- **Review periodically**: Contracts change. Review your recurring templates once a year to ensure amounts and terms are still correct.

### ✅ Accuracy
- **Use generic descriptions**: Instead of "Rent for January", use "Monthly Rent" as the description, since it will be copied to every future entry.
- **Double-check accounts**: Ensure the income/expense accounts are correct, as mistakes will be repeated every cycle.

### 🔒 Control
- **Keep them as Draft**: Let the system create Draft entries so you can verify them before Posting.

---

## Troubleshooting

### Common Questions

**Q: Why didn't my template generate an entry today?**

A: Check the **Next Run Date**. If it's in the future, it won't run yet. Also check the **Status**—it must be **Active**.

**Q: Can I stop a template without deleting it?**

A: Yes! Change the **Status** to **Inactive** or **Archived** (depending on options). This pauses generation but keeps the history.

**Q: Does it send the invoice to the customer automatically?**

A: Currently, the system creates the invoice. Sending it (via email) is usually a separate step or automation rule, depending on your configuration.

---

## Related Documentation

- [Journal Entries](journal-entries.md)
- [Customer Invoices](customer-invoices.md)
- [Vendor Bills](vendor-bills.md)
