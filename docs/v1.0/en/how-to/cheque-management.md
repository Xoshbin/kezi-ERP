---
title: Cheque Management
icon: heroicon-o-banknotes
order: 10
---

# Cheque Management: Handling Inbound & Outbound Cheques

This guide covers the complete lifecycle of cheques in Kezi, from receiving a customer cheque to banking it, clearing it, or handling rejections.

---

## What is Cheque Management?

Cheque Management allows you to track post-dated cheques (PDC) and current cheques separately from your cash and bank balances until they are actually cleared.

**Why is this important?**
1.  **Post-Dated Cheques**: Many businesses receive cheques dated for the future. You need to record the receipt but not increase your bank balance yet.
2.  **Tracking**: Know exactly which cheques are in your drawer (On Hand) versus which ones are at the bank.
3.  **Handling Rejections**: Easily manage bounced cheques without messing up your accounting.

---

## Where to Find It

Navigate to: **Accounting → Accounting → Cheques**

You will see two main types of cheques:
*   **Inbound (Customer)**: Money coming in.
*   **Outbound (Vendor)**: Money going out.

---

## The Cheque Lifecycle

Every cheque goes through a lifecycle. Just like a physical cheque moves from hand to bank, the system tracks these steps:

### For Inbound Cheques (Customer)

1.  **Draft**: You are entering the details. No journal entry yet.
2.  **On Hand**: You have confirmed the cheque and it is physically in your possession.
    *   *Journal*: Dr Cheques on Hand / Cr Customer (Accounts Receivable)
3.  **Deposited / Presented**: You have taken the cheque to the bank, but the money hasn't cleared yet.
    *   *Journal*: Dr Cheques under Collection / Cr Cheques on Hand
4.  **Cleared**: The bank confirms the money is in your account.
    *   *Journal*: Dr Bank / Cr Cheques under Collection
5.  **Rejected**: The cheque bounced.
    *   *Journal*: Dr Customer (AR) / Cr Cheques under Collection (Reversing the payment)

---

## How to Manage Cheques

### Receiving a Cheque (Inbound)

1.  Go to **Accounting → Cheques**.
2.  Click **Create**.
3.  Select **Type**: "Inbound".
4.  Fill in the details:
    *   **Contact**: The customer giving you the cheque.
    *   **Bank Account**: The bank where you will deposit it (your bank).
    *   **Cheque Number**: The number printed on the cheque.
    *   **Amount**: The value.
    *   **Cheque Date**: The date written on the cheque (Maturity date).
    *   **Transaction Date**: Today's date (when you received it).
5.  Click **Create**. The status is **Draft**.
6.  Click **Confirm** to move to **On Hand**.

> [!NOTE]
> The system automatically handles the accounting entries for each status change.

### Depositing to Bank

When you go to the bank:
1.  Open the **On Hand** cheque.
2.  Click **Deposit**.
3.  The status changes to **Presented**.

### Clearing or Rejecting

When you check your bank statement:
*   **If the money arrived**: Open the cheque and click **Clear**. The money is now in your bank account.
*   **If the cheque bounced**: Open the cheque and click **Reject**. The debt is returned to the customer's balance.

---

## Outbound Cheques (Paying Vendors)

The process is similar but reversed:
1.  **Draft**: Preparing the cheque.
2.  **On Hand**: You wrote the cheque.
3.  **Presented**: You gave it to the vendor.
4.  **Cleared**: The vendor took the money from your account.

---

## Best Practices

### 📅 Managing Dates
*   **Transaction Date**: When you physically got the paper.
*   **Cheque Date**: When the money can be taken. Always keep these accurate for better cash flow forecasting.

### 🔍 Regular Review
*   Filter by **Status** to see how many cheques are "On Hand" vs "Presented".
*   If a cheque is "Presented" for a long time, check your bank statement.

### 🔒 Security
*   Only authorized users should verify and change statuses to "Cleared".

---

## Troubleshooting

### Q: I made a mistake on the amount. Can I edit?
A: If the cheque is in **Draft**, yes. If it is **On Hand** or later, you might need to revert the status or cancel it first, depending on your permissions.

### Q: Why isn't my bank balance updating?
A: The bank balance only updates when the status reaches **Cleared**. Before that, it is in a "Cheques under Collection" or "Cheques on Hand" holding account.
