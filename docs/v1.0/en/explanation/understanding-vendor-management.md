---
title: Understanding Vendor Management
icon: heroicon-o-users
order: 2
---

# Understanding Vendor Management: Managing Your Suppliers

This guide explains how to manage vendors (suppliers) in the system. Whether you're setting up a new supplier, updating their banking info, or checking purchase history, we've got you covered.

---

## What is a Vendor?

Think of a **Vendor** as any person or company you buy goods or services from. In our system, they are unique "Partners" marked specifically as suppliers.

**Why does this matter?**
1.  **Streamlined Purchasing**: Pre-fill purchase orders with vendor details and prices.
2.  **Accurate Accounting**: Automatically link bills to the correct payable accounts.
3.  **Better Relationships**: Keep track of all interactions, emails, and transaction history in one place.

---

## Where to Find It

Navigate to: **Accounting → Partners** (or **Purchase → Vendors** if available)

You can filter the list to show only Vendors by using the **Type** filter.

> **💡 Tip**: Look for the Help button in the top-right corner—it opens this guide!

---

## Creating a Vendor

Let's walk through creating a new vendor profile step by step.

### Step 1: Start Fresh

Navigate to the Partners list and click **Create Partner**.

You'll see a form with these sections:

### 1. Basic Information

| Field | What to Enter | Example |
|---|---|---|
| **Name** | The legal name of the company or person | "Acme Supplies Ltd" |
| **Type** | Choose **Vendor** (or **Both** if they also buy from you) | Vendor |
| **Tax** | Their default tax rate for purchases | "Purchase Tax 15%" |
| **Status** | Keep active to use them in transactions | Active |

### 2. Contact & Address

Fill in the details to ensure orders reach the right place:
- **Contact Person**: Who do you talk to?
- **Email/Phone**: For sending POs and reminders.
- **Address**: Where do they ship from?

### 3. Accounting Configuration

This is crucial for your financial books:

| Field | Description |
|---|---|
| **Receivable Account** | (If they are also a customer) Where money owed *by* them goes. |
| **Payable Account** | Where money you owe *to* them tracks. Default is usually "Accounts Payable". |
| **Fiscal Position** | Used for automatic tax mapping (e.g., International Vendors). |
| **Withholding Tax** | If you need to withhold tax from payments to this vendor. |

---

## Vendor Price Lists

You can set up specific prices for products when buying from this vendor. This happens on the **Product** page usually, or within the Purchase module settings, linked to the vendor.

*Note: Detailed price list management is covered in the Product documentation.*

---

## Vendor 360 View

Once a vendor is created, their profile becomes a central hub. Click on a vendor name to view their **Partner Ledger** and history.

You can see:
- **Vendor Bills**: All bills received from them.
- **Payments**: Money you've sent them.
- **Purchase Orders**: Active and past orders.
- **Balance**: How much you currently owe them (Vendor Outstanding).

---

## Best Practices

### ✅ Accuracy
- **Keep Tax IDs Updated**: Essential for tax reporting compliance.
- **Set Payment Terms**: defined on bills/invoices to avoid overdue alerts.

### 🔒 Security
- **Verify Bank Details**: Always double-check banking info before large payments (store in custom fields or notes if applicable).

### 🧹 Hygiene
- **Don't Duplicate**: Search before creating a new vendor to avoid split history.
- **Archive Inactive Vendors**: If you stop doing business, set them to Inactive instead of deleting.

---

## Troubleshooting

### Common Questions

**Q: Why can't I see this partner in the vendor list?**
A: Check the **Type** field. Ensure it is set to **Vendor** or **Both**. If it's set to "Customer", it won't appear in vendor-specific filters.

**Q: Can a partner be both a customer and a vendor?**
A: Yes! Select **Both** in the Type field. This is common for inter-company setups or trading partners.

**Q: I created a vendor but they don't have a Payable Account.**
A: The system usually assigns a default. If it's missing, you can create one directly from the dropdown or ask your accountant.

> **💡 Still stuck?** Contact support with your specific issue!

---

## Related Documentation

- [Vendor Bills](understanding-vendor-bills.md) - How to record bills from vendors
- [Purchase Orders](understanding-purchase-orders.md) - Creating orders for vendors
- [Fiscal Positions](understanding-fiscal-positions.md) - Managing tax rules for different regions
