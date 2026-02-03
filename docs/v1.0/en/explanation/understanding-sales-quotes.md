---
title: Understanding Sales Quotes
icon: heroicon-o-document-text
order: 8
---

# Understanding Sales Quotes

This guide explains how to use Sales Quotes to propose prices and products to your customers before finalizing a sale. Written for sales teams and business owners, it covers creating quotes, sending them to customers, and converting them into orders.

---

## What is a Sales Quote?

Think of a **Sales Quote** like a specialized "menu" you create for a specific customer. It lists the items they want and the price you're offering, but it's not a final bill yet—it's an invitation to do business.

**The Three Key Players:**
1.  **You (The Seller)**: You create the quote with your best offer.
2.  **The Customer**: They review the quote and decide whether to accept it.
3.  **The System**: Tracks the quote's validity and status so you don't forget to follow up.

**Why does this matter?**
1.  **Professionalism**: Sending a formal quote looks better than a quick email or phone call.
2.  **Accuracy**: Ensures both you and the customer agree on exactly *what* is being sold and for *how much*.
3.  **Workflow**: Easily turns into a Sales Order or Invoice without re-typing everything.

---

## How It Works in Kezi ERP

### 1. Quote Statuses

Every quote moves through a specific lifecycle to help you track its progress.

| Status | Color | Description |
| :--- | :--- | :--- |
| **Draft** | Gray | You are still working on it. Not sent to the customer yet. |
| **Sent** | Blue | You've sent it to the customer. Now waiting for their reply. |
| **Accepted** | Green | Great news! The customer said "Yes". |
| **Rejected** | Red | The customer said "No". |
| **Expired** | Orange | The validity date has passed without a response. |
| **Converted** | Primary | The accepted quote has become a Sales Order or Invoice. |
| **Cancelled** | Gray | You decided to stop this quote manually. |

### 2. Validity Periods

Quotes often have an expiration date (e.g., "Valid for 30 days"). This protects you from price changes.
*   The system monitors the **Valid Until** date.
*   If that date passes, the quote logic considers it **Expired**.
*   **Best Practice**: Always set a validity date to create urgency!

---

## The Workflow: Step-by-Step

### Step 1: Creating the Quote 📝

Navigate to: **Sales → Quotes → Create Quote**

1.  **Customer**: Select the customer who requested the quote.
2.  **Quote Date**: Today's date is automatic.
3.  **Valid Until**: Set a date (e.g., 2 weeks from now) for when the offer ends.

### Step 2: Adding Products/Services

In the **Lines** section, add what you are selling:
1.  Select a **Product**.
2.  Check the **Quantity**.
3.  Verify the **Unit Price** (it pulls from the product list, but you can adjust it).
4.  Add a **Discount** if you want to apply a special deal.

### Step 3: Sending to Customer ✉️

Once you are happy with the draft:
1.  Click **Mark as Sent**.
2.  This changes the status to **Sent**.
3.  You can now print the PDF or email it to the customer.

### Step 4: Customer Confirmation ✅

When the customer replies:
*   **If they accept**: Click **Mark as Accepted**.
*   **If they decline**: Click **Mark as Rejected**. You can add a reason (e.g., "Price too high") for future analysis.

### Step 5: Converting to Sales Order 🔄

After acceptance, you don't need to re-type the data.
1.  Click **Convert to Sales Order**.
2.  The system creates a new **Sales Order** with all the same items and prices.
3.  The Quote status changes to **Converted**.

---

## Visualizing the Quote Lifecycle

```
    [Draft] 
       │
       ▼
     [Sent] ───┐
       │       │
       ▼       ▼
 [Accepted] [Rejected]
    │    │
    │    └─ [Expired]
    ▼
[Converted]
```

---

## Best Practices

1.  **Set Realistic Validity Periods**: Don't leave quotes open forever. Prices change! 15-30 days is standard.
2.  **Use Notes**: Add a personal note in the "Notes" field (e.g., "Includes free installation") to add value.
3.  **Follow Up**: Check your **Sent** quotes weekly. If one is about to expire, give the customer a friendly nudge.
4.  **Track Rejections**: If a customer rejects a quote, mark it as "Rejected" instead of just deleting it. This helps you understand your win/loss rate later.

---

## Troubleshooting Common Questions

**Q: Why can't I edit a Sent quote?**
A: Once a quote is **Sent**, it's locked to ensure what you see matches what the customer has. If you need to change it, create a **Revision** or revert it to Draft (if permitted).

**Q: What happens to Expired quotes?**
A: They stay in the system but are marked as **Expired**. You can still open them and propose a new, updated quote if the customer comes back later.

**Q: Can I skip the Sales Order and go straight to Invoice?**
A: Yes! If you have a simple workflow, you can use the **Convert to Invoice** action directly from an Accepted quote.

---

## Related Documentation

- [Customer Invoices](customer-invoices.md) - The final step of the sale
- [Payment Terms](payment-terms-guide.md) - Understanding payment deadlines
- [Tax Management](tax-management.md) - How taxes are calculated on quotes
