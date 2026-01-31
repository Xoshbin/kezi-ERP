---
title: Credit Notes
icon: heroicon-o-arrow-uturn-left
order: 7
---

# Credit Notes: Handling Customer Returns & Corrections

This guide explains how to use Credit Notes to reduce the amount a customer owes you. Whether dealing with product returns, price corrections, or invoice cancellations, Credit Notes help maintain accurate financial records.

---

## What is a Credit Note?

A **Credit Note** is a document that reduces the amount a customer owes your business. Think of it as the opposite of an invoice—instead of asking for money, you're reducing what they owe.

**Common reasons to issue a Credit Note:**

| Reason | Example |
|--------|---------|
| **Customer Returns** | Customer returns 2 defective laptops |
| **Price Correction** | Original invoice had wrong price (too high) |
| **Discount Applied Later** | Agreed 10% discount after invoice was posted |
| **Invoice Cancellation** | Customer cancelled order after invoicing |
| **Quantity Adjustment** | Delivered 8 items but invoiced 10 |

**Accounting Impact**: A posted Credit Note decreases revenue and reduces accounts receivable (the amount customers owe you).

---

## Where to Find It

Navigate to: **Accounting → Adjustment Documents**

When creating a new Adjustment Document, select **Credit Note** as the type.

> [!TIP]
> Credit Notes in this system are a type of Adjustment Document. This unifies all corrections (credit notes, debit notes, miscellaneous adjustments) in one place for easier management.

---

## Credit Note Workflow

Your credit note goes through these stages:

┌─────────┐      ┌─────────┐      ┌─────────┐
│  Draft  │ ──▶ │ Posted  │ ──▶ │ Applied │
└─────────┘      └─────────┘      └─────────┘
    📝              ✅              💰

### 📝 Draft
- You can edit all details
- No accounting impact yet
- Safe to delete if not needed

### ✅ Posted
- Locked for editing
- Journal entry created
- Reduces customer's outstanding balance
- Ready to be applied to invoices or refunded

### 💰 Applied
- Credit has been used to reduce an open invoice
- Or refund has been issued to customer

---

## Creating a Credit Note

### Step 1: Start the Credit Note

1. Go to **Accounting → Adjustment Documents**
2. Click **Create Adjustment Document**
3. Select **Credit Note** as the Type

### Step 2: Fill in the Header

| Field | What to Enter | Example |
|-------|---------------|---------|
| **Type** | Select "Credit Note" | Credit Note |
| **Customer** | Who is receiving the credit | ABC Company |
| **Currency** | Same as original invoice | USD |
| **Date** | Date of the credit note | Today's date |
| **Original Invoice** | Link to the invoice being corrected (optional) | INV-2024-001 |
| **Reason** | Why you're issuing this credit | "Return of 2 defective items" |

> [!IMPORTANT]
> Linking to the original invoice helps with tracking and audit trails, but is not mandatory.

### Step 3: Add Line Items

Click **Add Line Item** and enter:

| Field | Description | Example |
|-------|-------------|---------|
| **Product** | Item being credited | Laptop Model X |
| **Description** | Detail of the credit | "Return - defective screen" |
| **Quantity** | Number of items | 2 |
| **Unit Price** | Price per item | 500.00 |
| **Account** | Revenue account (auto-filled) | Sales Revenue |
| **Tax** | If tax was on original invoice | 10% VAT |

**Amounts will be negative** to indicate a reduction.

### Step 4: Review and Post

Before clicking **Post**, verify:

- [ ] Customer is correct
- [ ] Line items match what's being credited
- [ ] Amounts and taxes are accurate
- [ ] Reason is documented for auditing

Click **Post** to finalize the credit note.

---

## What Happens Behind the Scenes

When you post a credit note for a $1,000 return (with 10% tax), the system creates:

```
┌─────────────────────────────────────────────────────────────┐
│  Dr. Sales Revenue                    $1,000.00             │
│  Dr. Sales Tax Payable                  $100.00             │
│      Cr. Accounts Receivable                     $1,100.00  │
└─────────────────────────────────────────────────────────────┘
```

**In plain English**: We're reversing the original sale—decreasing our recorded revenue and reducing what the customer owes us.

---

## Applying a Credit Note

Once posted, you have two options for the credit:

### Option 1: Apply to Open Invoice

The credit reduces the customer's outstanding invoice balance.

**Example**: Customer has $5,000 open invoice. You issue $1,100 credit note.
- New balance: $5,000 - $1,100 = **$3,900 remaining**

### Option 2: Issue Refund

If the customer has no open invoices (or prefers cash back):

1. Create a **Payment** (outbound)
2. Link it to the credit note
3. Record payment via bank transfer, cash, or check

---

## Common Scenarios

### Scenario 1: Customer Returns Defective Items

**The Situation**: A customer bought 10 laptops at $500 each. Two were defective and returned.

**What to do**:

1. Create a new Adjustment Document → Type: **Credit Note**
2. Select the customer
3. Link to the original invoice (optional)
4. Add line:
   - Product: Laptop
   - Quantity: 2
   - Price: $500
   - Tax: Same as original invoice
5. Reason: "Return of 2 defective units - RMA #12345"
6. **Post** the credit note

**Result**: 
- Customer's balance reduced by $1,100 (including tax)
- Sales revenue decreased by $1,000
- Tax liability reduced by $100
- If storable products: Inventory stock increased by 2 units

### Scenario 2: Price Correction

**The Situation**: You invoiced a customer $200/unit but the agreed price was $180/unit for 50 units.

**What to do**:

1. Create a Credit Note
2. Calculate difference: ($200 - $180) × 50 = **$1,000**
3. Add line with the price difference
4. Reason: "Price correction per agreement dated [date]"
5. **Post**

### Scenario 3: Full Invoice Cancellation

**The Situation**: Customer cancelled order but invoice was already posted.

**What to do**:

1. Create a Credit Note for the **full amount** of the original invoice
2. Link to the original invoice
3. Copy all line items (or add manually)
4. Reason: "Order cancelled - customer request"
5. **Post**

The credit note fully offsets the original invoice.

---

## Multi-Currency Credit Notes

When the original invoice was in a foreign currency:

1. Use the **same currency** as the original invoice
2. System captures the current exchange rate
3. Currency conversion handled automatically
4. Exchange differences recorded if rates changed

---

## Best Practices

### ✍️ Document Everything
Always include a clear **Reason** explaining why the credit was issued. Future auditors (and future you) will appreciate it.

### 🔗 Link to Original Documents
When possible, link the credit note to the original invoice. This creates a clear audit trail.

### 📋 Use Reference Numbers
Include RMA numbers, customer complaint IDs, or other tracking references in the description.

### 📊 Review Before Posting
Once posted, credit notes cannot be edited. Double-check all amounts before confirming.

### 🔒 Monitor Unusual Activity
Large or frequent credit notes should be reviewed by management to prevent fraud.

---

## Troubleshooting

### "Cannot Post" Error

**Check**:
- Are all required fields filled?
- Does the date fall within an open accounting period?
- Is the currency valid and active?

### Credit Note Not Reducing Balance

**Check**:
- Was the credit note posted (not just saved as draft)?
- Is it linked to the correct customer?
- Has it been applied properly?

### Tax Calculation Wrong

**Check**:
- Is the same tax rate used as the original invoice?
- Check the customer's fiscal position settings
- Verify product tax configuration

---

## Related Documentation

- [Customer Invoices](customer-invoices.md) - Creating and managing invoices
- [Adjustment Documents](adjustment-documents.md) - General adjustment document guide
- [Payments](payments.md) - Processing refunds and payments

---

Credit Notes are essential for maintaining accurate financial records when dealing with returns, corrections, and cancellations. By properly documenting each credit, you ensure clean audit trails and accurate customer balances.
