---
title: Understanding Vendor Bills
icon: heroicon-o-light-bulb
order: 10
---

A vendor bill is the digital record of a commercial invoice received from a supplier. In the specific context of Kezi , it represents more than just a request for payment—it is the central document that triggers expense recognition, inventory cost updates, and asset capitalization.

This guide explains the conceptual framework of vendor bills, their lifecycle, and their role in the accrual accounting process.

---

## The Role of Vendor Bills

The vendor bill serves four primary functions in the accounting cycle:

1.  **Liability Establishment**: It formally recognizes that the company owes money to a third party (Accounts Payable).
2.  **Expense Recognition**: It records the cost in the specific period it was incurred, regardless of when payment is made (Accrual Principle).
3.  **Inventory Valuation**: For stockable goods, the bill finalizes the cost of items received, which may differ from the initial Purchase Order price.
4.  **Tax Reclamation**: It acts as the substantiating document for reclaiming Input VAT or other recoverable taxes.

### Vendor Bill vs. Purchase Order

It is common to confuse these documents. The **Purchase Order (PO)** is a *request* to buy goods, essentially a contract sent *to* the vendor. The **Vendor Bill** is the request for payment received *from* the vendor.

| Feature | Purchase Order | Vendor Bill |
| :--- | :--- | :--- |
| **Direction** | Outbound (To Vendor) | Inbound (From Vendor) |
| **Accounting Impact** | None (usually) | Credits Accounts Payable |
| **Purpose** | Authorization to buy | Obligation to pay |
| **Timing** | Before delivery | After delivery/service |

---

## Bill Lifecycle

A vendor bill flows through a strict state machine to ensure data integrity.

### 1. Draft
The initial state. A draft bill is a workspace. No accounting entries are generated, and the document can be freely edited or deleted. This is where data entry happens and validation checks are performed.

### 2. Posted
A posted bill is an immutable accounting record. Moving a bill to "Posted" status:
*   Generates the Journal Entry.
*   Locks the fields to prevent editing.
*   Updates the Vendor's balance.
*   Updates the General Ledger.

To correct a posted bill, you cannot simply edit it; you must issue a **Debit Note** (Credit Note from the vendor's perspective) or cancel it to reverse the entry.

### 3. Paid (or Partially Paid)
When a payment is registered against the bill, its status updates to reflect the balance due.
*   **Partially Paid**: The bill has been matched with a payment less than the total amount.
*   **Paid**: The bill is fully reconciled with one or more payments.

### 4. Cancelled
A cancelled bill is effectively voided. The system reverses any journal entries associated with it, ensuring the net accounting impact is zero.

---

## The Accounting Impact

When a vendor bill is **Posted**, the system automatically generates a double-entry journal. Understanding this entry is crucial for verifying your financial statements.

### Standard Expense Bill
For a simple purchase of services or consumables:

| Account | Debit | Credit |
| :--- | :--- | :--- |
| **Expense Account** (e.g., Office Supplies) | Increase (Dr) | |
| **Tax Account** (Input VAT) | Increase (Dr) | |
| **Accounts Payable** (Vendor Liability) | | Increase (Cr) |

### Asset Acquisition Bill
For purchasing Fixed Assets (e.g., Laptops, Machinery):

| Account | Debit | Credit |
| :--- | :--- | :--- |
| **Fixed Asset Clearing** / **Asset Account** | Increase (Dr) | |
| **Tax Account** (Input VAT) | Increase (Dr) | |
| **Accounts Payable** | | Increase (Cr) |

---

## Purchase Categories

The system treats bills differently depending on what is being bought:

### 1. Inventory Purchases
Bills for stockable goods affect the **Stock Valuation**. If you use "Real-time Valuation" (Perpetual Inventory), the bill might debit an "Inventory Request" or "Stock Interim" account rather than a direct expense, waiting for the goods receipt to balance the transaction.

### 2. Service & Consumables
These are direct expenses. They hit the Profit & Loss statement immediately upon posting the bill.

### 3. Capital Expenditures (CapEx)
These bills do not hit the P&L immediately. Instead, they create an **Asset** on the Balance Sheet. The expense is recognized over time through *Depreciation*.

---

## Automated Approval Chains

To prevent fraud and maintain budget control, vendor bills often require approval before they can be Posted. The system supports tiered approval limits based on the bill amount:

*   **User Level**: Can create drafts but requires a manager to post.
*   **Manager Level**: Can approve bills up to a specific limit (e.g., 5,000,000 IQD).
*   **Director/CFO Level**: Required for amounts exceeding standard operational limits.

This **Segregation of Duties** ensures that the person requesting the purchase isn't always the same person authorizing the payment.
