---
title: Payments
icon: heroicon-o-credit-card
order: 9
---


This comprehensive guide explains how payments work in the accounting system, covering creation, management, multi-currency support, and reconciliation. Written for all users — accountants and non‑accountants — it provides practical guidance following double‑entry accounting best practices.

---

## What is a Payment?

A Payment represents money movement between your company and a business partner (customer or vendor). Payments are fundamental to cash flow management and accounts receivable/payable settlement.

### Payment Types
- **Inbound Payment** — Money received from customers (reduces Accounts Receivable)
- **Outbound Payment** — Money sent to vendors (reduces Accounts Payable)

### Payment Categories
- **Settlement Payment** — Linked to specific invoices or vendor bills to settle outstanding amounts
- **Partner Advance/Credit** — Standalone payments for customer deposits or vendor advances

Each Payment includes:
- Journal (Bank or Cash account where money moved)
- Payment date and currency
- Amount in both payment currency and company base currency
- Payment method and optional reference
- Partner information and status tracking

---

## System Requirements

### Journal Configuration
- **Bank Journals**: At least one bank journal configured for payment processing
- **Payment Methods**: Payment methods must be set up for processing options
- **Currency Setup**: Currencies must be active and exchange rates configured
- **Partner Records**: Customers and vendors must exist for payment linking

### Prerequisites
1. **Bank Account Setup**: Bank journals properly configured with accounting integration
2. **Partner Information**: Customer or vendor records created with payment details
3. **Invoice/Bill Records**: For linked payments, invoices or bills must be posted
4. **User Permissions**: Appropriate access to banking and payment features

---

## Where to find it in the UI

Navigate to **Accounting → Banking & Cash → Payments**

Payments also appear in:
- **Customer Invoices**: "Register Payment" action for posted unpaid invoices
- **Vendor Bills**: "Register Payment" action for posted unpaid bills  
- **Bank Reconciliation**: Confirmed payments available for matching with bank statements
- **Dashboard**: Recent payment activity and cash flow summaries

**Tip**: The header's Help/Docs button opens this guide.

---

## Payment Status Workflow

Payments follow a controlled workflow to ensure data integrity:

### Draft
- Newly created payment, fully editable
- Can be modified or deleted
- No accounting impact yet
- No journal entries created

### Confirmed
- Payment finalized and posted to accounting
- Journal entries created automatically
- Immutable to preserve audit trail
- Cannot be edited or deleted
- Exchange rates locked at confirmation

### Reconciled
- Payment matched with bank statement line
- Confirms actual bank transaction occurred
- Additional reconciliation journal entry created
- Final status in normal workflow

### Canceled
- Confirmed payment that has been reversed
- Reversal journal entry created
- Maintains complete audit trail
- Cannot be undone once canceled

**Important**: Only Draft payments can be edited or deleted. Confirmed payments require proper reversal procedures to maintain accounting integrity.

---

## Payment Methods

The system supports multiple payment methods:

- **Manual** — General manual entry
- **Check** — Paper check payments
- **Bank Transfer** — Electronic bank transfers
- **Credit Card** — Credit card transactions
- **Debit Card** — Debit card transactions
- **Cash** — Physical cash payments
- **Wire Transfer** — International wire transfers
- **ACH** — Automated Clearing House transfers
- **SEPA** — Single Euro Payments Area transfers
- **Online Payment** — Digital payment platforms

---

## Creating Payments

### 1) Standalone Payment from Payments Menu

Use this for money movements not directly tied to specific invoices/bills (customer deposits, vendor advances, or immediate payments to be linked later).

**Steps:**
1. Go to **Accounting → Banking & Cash → Payments**
2. Click **Create**
3. Fill the payment form:
   - **Payment Type**: Select Receive (inbound) or Send (outbound)
   - **Partner**: Choose the customer or vendor
   - **Journal**: Select the bank or cash account
   - **Payment Date**: When the money moved
   - **Currency and Amount**: Payment currency and amount
   - **Payment Method**: Choose from available methods
   - **Reference**: Optional internal note or bank reference
4. **Save** to create as Draft
5. **Confirm** when ready to post to accounting

**Result:**
- Draft payment created and editable
- Upon confirmation, journal entries posted automatically
- Payment available for settlement or reconciliation

### 2) Register Payment on Invoice (Customer Payment)

Use this to collect money for posted customer invoices.

**Prerequisites:**
- Invoice must be Posted (not Draft)
- Remaining amount due must exist

**Steps:**
1. Open the posted Invoice
2. Click **Register Payment** (banknotes icon)
3. Fill the payment dialog:
   - **Journal**: Usually your main bank account
   - **Payment Date**: When money was received
   - **Amount**: Defaults to remaining amount; adjust for partial payments
   - **Reference**: Optional bank reference or note
4. **Confirm** to create and post payment

**What happens:**
- Inbound payment created and immediately confirmed
- Payment linked to invoice, reducing due amount
- Invoice status updates (Partially Paid or Paid)
- Journal entries posted automatically
- Payment available for bank reconciliation

### 3) Register Payment on Vendor Bill (Vendor Payment)

Use this to pay posted vendor bills.

**Prerequisites:**
- Bill must be Posted (not Draft)
- Remaining amount due must exist

**Steps:**
1. Open the posted Vendor Bill
2. Click **Register Payment** (banknotes icon)
3. Fill the payment dialog:
   - **Journal**: Usually your main bank account
   - **Payment Date**: When money was sent
   - **Amount**: Defaults to remaining amount; adjust for partial payments
   - **Reference**: Optional reference or note
4. **Confirm** to create and post payment

**What happens:**
- Outbound payment created and immediately confirmed
- Payment linked to bill, reducing due amount
- Bill status updates (Partially Paid or Paid)
- Journal entries posted automatically
- Payment available for bank reconciliation

---

## Payment Confirmation and Management

### Confirming Payments

**Draft payments** can be confirmed to post them to accounting:

1. Open the Draft payment
2. Click **Confirm Payment**
3. Confirm the action when prompted

**Upon confirmation:**
- Journal entries created automatically
- Exchange rates locked (for multi-currency)
- Payment becomes immutable
- Status changes to Confirmed

### Editing Restrictions

- **Draft payments**: Fully editable and deletable
- **Confirmed payments**: Immutable, cannot be edited or deleted
- **Reconciled payments**: Final status, no changes allowed

### Deletion Rules

- **Draft payments**: Can be deleted safely
- **Confirmed/Reconciled payments**: Cannot be deleted
- Use cancellation for confirmed payments requiring reversal

---

## Multi-Currency Payments

The system handles payments in any configured currency with automatic conversion:

### Currency Handling
- **Payment Currency**: The currency in which payment was made
- **Company Currency**: Your company's base currency for reporting
- **Exchange Rate**: Captured automatically at payment confirmation
- **Conversion**: Automatic conversion to company currency

### Exchange Rate Management
- Rates captured at payment date
- Locked upon confirmation to prevent manipulation
- Used for realized gain/loss calculations
- Displayed in payment details for audit trail

### Realized Exchange Gains/Losses
When settling foreign currency invoices/bills:
- System calculates difference between invoice and payment rates
- Automatic journal entries for realized gains/losses
- Posted to configured exchange gain/loss accounts
- Maintains accurate financial reporting

**Example**: 
- Invoice: $1,000 USD at rate 1,300 IQD/USD = 1,300,000 IQD
- Payment: $1,000 USD at rate 1,310 IQD/USD = 1,310,000 IQD
- Realized Loss: 10,000 IQD posted to exchange loss account

---

## Payment Relations and Document Links

### Linked Documents
Payments can be linked to multiple documents:
- **Invoices**: Customer payment settlements
- **Vendor Bills**: Vendor payment settlements
- **Amount Applied**: Track specific amounts allocated to each document

### Viewing Relations
Each payment shows related information:
- **Invoices Tab**: All linked customer invoices
- **Vendor Bills Tab**: All linked vendor bills
- **Journal Entries Tab**: All accounting entries
- **Bank Statement Lines Tab**: Reconciliation history

### Document Settlement
- Payments can settle multiple documents
- Partial settlements supported
- Remaining balances tracked automatically
- Settlement history maintained for audit

---

## Bank Reconciliation

### Reconciliation Process
1. Import or create bank statements
2. Navigate to **Accounting → Banking & Cash → Bank Statements**
3. Open statement and click **Reconcile**
4. Match confirmed payments to statement lines
5. System updates payment status to Reconciled

### Reconciliation Effects
- Payment status changes to Reconciled
- Bank statement line marked as reconciled
- Additional reconciliation journal entry created
- Confirms actual bank transaction occurred

### Reconciliation Requirements
- Payments must be Confirmed before reconciliation
- Amounts must match between payment and statement line
- Currency conversion handled automatically
- Multiple payments can reconcile to one statement line

---

## Best Practices and Tips

### Payment Management
- **Confirm carefully**: Confirmed payments cannot be edited
- **Use references**: Add bank references for easier reconciliation
- **Regular reconciliation**: Keep books aligned with bank statements
- **Partial payments**: Use for installment collections/payments

### Multi-Currency Best Practices
- **Monitor exchange rates**: Significant rate changes affect cash flow
- **Hedge currency risk**: Consider forward contracts for large amounts
- **Review gain/loss**: Monitor exchange gain/loss impact on profitability

### Audit and Compliance
- **Maintain documentation**: Keep supporting documents for all payments
- **Review journal entries**: Verify accounting impact of payments
- **Lock date compliance**: Respect accounting period lock dates
- **Segregation of duties**: Separate payment creation and confirmation

---

## Troubleshooting

### Common Issues

**Q: Cannot edit confirmed payment**
A: Confirmed payments are immutable. Create a reversal entry if changes needed.

**Q: Payment not appearing in reconciliation**
A: Ensure payment is Confirmed and matches statement currency/amount.

**Q: Exchange rate seems incorrect**
A: Exchange rates are locked at confirmation. Check rate source and date.

**Q: Cannot delete payment**
A: Only Draft payments can be deleted. Use cancellation for confirmed payments.

### Error Messages

**"Only draft payments can be confirmed"**
- Payment is already confirmed or in wrong status
- Refresh page and check current status

**"Payment date is in locked period"**
- Payment date falls within locked accounting period
- Change date or request period unlock from administrator

**"Insufficient remaining amount"**
- Trying to apply more than document's remaining balance
- Check document status and previous payments

---

## Frequently Asked Questions

**Q: Can I delete a confirmed payment?**
A: No. Financial documents become immutable once confirmed to preserve audit trail. Use cancellation for reversals.

**Q: Can I do partial payments?**
A: Yes. Enter a smaller amount when registering payment. The document remains partially paid with updated balance.

**Q: How does foreign currency work?**
A: We store amounts in both payment and company currencies. Exchange gains/losses are calculated and posted automatically when settling documents.

**Q: What's the difference between settlement and advance payments?**
A: Settlement payments are linked to specific invoices/bills. Advance payments are standalone and can be applied to future documents.

**Q: Can one payment settle multiple invoices?**
A: Yes. Use document links to allocate payment amounts across multiple invoices or bills.

**Q: How do I handle payment fees?**
A: Record fees separately through bank statements or miscellaneous journal entries. Payment amounts should reflect net amounts received/sent.

---

## Glossary

- **Partner**: Customer or vendor in the system
- **Journal**: Bank or cash account where transactions are recorded
- **Settlement**: Applying payment to specific invoice or bill
- **Reconciliation**: Matching system payments to bank statement lines
- **Document Link**: Connection between payment and invoice/bill with amount applied
- **Exchange Rate**: Currency conversion rate locked at payment confirmation
- **Realized Gain/Loss**: Exchange difference when settling foreign currency documents

---

Need more help? Use the Help/Docs button in the Payments section or contact your system administrator for additional support.
