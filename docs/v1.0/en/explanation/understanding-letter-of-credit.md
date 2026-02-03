---
title: Understanding Letter of Credit (LC) Management
icon: heroicon-o-document-text
order: 11
---

# Understanding Letter of Credit (LC) Management

This guide explains **Letters of Credit (LC)**—what they are, how they protect your business in international trade, and how to manage them efficiently in Kezi ERP. Whether you're importing raw materials or exporting finished goods, this module ensures your financial commitments are tracked, utilized, and accounted for with precision.

---

## What is a Letter of Credit (LC)?

Imagine you're buying a large shipment of electronics from a supplier in another country. You don't want to pay upfront before seeing the goods, and the supplier doesn't want to ship the goods without being sure they'll get paid.

A **Letter of Credit** is a "banker's promise." It's a document issued by your bank that guarantees payment to the seller, provided they deliver the goods and present the required documents (like shipping bills) as proof.

**In simple terms:**
The bank stands in the middle. They say to the seller: *"If you ship the goods and show us the paperwork, we promise to pay you on behalf of our customer."*

**Key Benefits:**
1. **Safety**: Reduced risk for both buyer and seller.
2. **Trust**: Banks act as neutral third parties.
3. **Financing**: Allows for better cash flow management in large trades.

---

## How it Works in Kezi ERP

Our system tracks the entire lifecycle of an LC, from the initial application to the final payment and closure.

### 1. The Setup: Creating an LC
When you decide to open an LC, you record the details in the system:
- **LC Number & Bank Reference**: To link it to your bank's records.
- **Beneficiary**: The vendor who will receive the payment.
- **Amount & Currency**: The total value guaranteed by the bank.
- **Incoterm**: The shipping terms (e.g., CIF, FOB) that define who pays for freight and insurance.

**Where to find this:** Accounting → LC Management → Letters of Credit

---

## The LC Lifecycle

An LC moves through several stages in the system:

1. **Draft**: You are still entering details or waiting for internal approval.
2. **Issued**: The bank has officially opened the LC. You can now start using it against purchases.
3. **Partially Utilized**: You've received some shipments and used part of the LC amount.
4. **Fully Utilized**: The entire amount of the LC has been used.
5. **Expired**: The LC has reached its end date and can no longer be used.
6. **Cancelled**: The trade was called off, and the LC was voided.

---

## Workflow: A Real-Life Example

Let's follow a typical import scenario.

### Scenario: Importing Raw Materials 🚢

You are importing $100,000 worth of fabric for your garment factory.

#### Step 1: Create and Issue the LC
You open a $100,000 LC with your bank. In the system, you create the LC record and move it to **Issued** status.
- **Balance**: $100,000
- **Status**: Issued

#### Step 2: First Shipment (Partial Utilization)
The supplier ships half the fabric ($30,000 worth) and sends a **Vendor Bill**.
When you record the Vendor Bill, you link it to the LC.
- **Action**: Utilize LC
- **Amount**: $30,000

**The system automatically:**
1. Deducts $30,000 from the LC Balance.
2. Updates LC Status to **Partially Utilized**.
3. Links the Vendor Bill to the LC for a perfect audit trail.

#### Step 3: Final Shipment (Full Utilization)
The supplier ships the remaining $70,000 worth of fabric. You record the final Vendor Bill and link it again.
- **Amount**: $70,000

**The system automatically:**
1. Deducts $70,000 from the balance.
2. Sets the LC Status to **Fully Utilized**.
3. Closes the LC as it's now complete.

---

## Managing Bank Charges 💸

Opening and maintaining an LC isn't free—banks charge commissions, swift fees, and amendment fees.

### Recording Charges
You can add charges directly to an LC at any time:
1. Open the LC record.
2. Go to the **Charges** tab.
3. Record the charge amount, date, and description.

**Accounting Impact:**
When you post a charge, the system creates a **Journal Entry** that:
- Debits your **Bank Charges** expense account.
- Credits your **Bank** account.

This ensures your P&L and Bank Balance are always accurate without manual double-entry.

---

## Best Practices

### 1. Monitor Expiry Dates
The system tracks the **Expiry Date** and **Latest Shipment Date**. Check these regularly to avoid shipments arriving after the LC has expired, which can lead to expensive bank penalties.

### 2. Match Incoterms
Ensure the Incoterm on your LC matches your Purchase Order. If your LC says "FOB" but your supplier expects "CIF," you'll have a discrepancy that stops payment.

### 3. Record Amendments Promptly
If the bank increases the LC amount or extends the date, use the **Amend** action in the system immediately so your reports stay current.

---

## Security & Controls

### Immutable Audit Trail
Like everything in Kezi ERP, LC records are auditable.
- **Posted Charges** cannot be edited—they must be reversed.
- **Utilizations** are locked once the linked Vendor Bill is posted.
- Every status change is logged with the user and timestamp.

### Permission Levels
- **Procurement Officers**: Can create draft LCs and link them to bills.
- **Finance Managers**: Can "Issue" LCs and approve bank charges.
- **Accountants**: Can view reports and ensure GL balances match bank statements.

---

## Common Questions

**Q: Can I use one LC for multiple Purchase Orders?**
A: Yes! You can link multiple Vendor Bills (from different POs) to a single LC as long as the total doesn't exceed the LC amount.

**Q: What if I spend more than the LC balance?**
A: The system will block you. You must either amend the LC to increase its amount or pay the excess through a standard bank transfer.

**Q: How do I handle currency differences?**
A: LCs are often in foreign currency (like USD). The system automatically handles the conversion to your base currency (IQD) using the daily exchange rate, tracking any exchange gains or losses.

---

## Related Documentation

- [Vendor Bills](vendor-bills.md) - How to record purchases.
- [Bank Reconciliation](bank-reconciliation.md) - Matching LC payments to bank statements.
- [Multi-Currency Support](multi-currency.md) - Understanding exchange rates.
